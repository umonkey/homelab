<?php
/**
 * Background task execution.
 *
 * Tasks are added using the `queue' method.
 *
 * One way to start the daemon is to use a system cron job, e.g., run the
 * script every minute.  The lock file won't let you run it multiple times
 * and exhaust resources.  Example cron job:
 *
 *   * * * * * php -f /var/www/acme/index.php taskq-run >/dev/null 2>&1
 *
 * Where index.php would call Framework_TaskQueue::run().
 *
 * Another way to run it is to use the fpm_finish_request function, if you
 * are using FPM.  You need to use the shutdown_handler for this.
 **/

class Framework_TaskQueue
{
    protected $db;
    protected $tableName = "taskq";

    /**
     * Task routing table.
     *
     * Read from config/tasks.php.  Keys are patterns, values are callables.
     * Patterns can contain placeholders, which are passed as arguments to the
     * callable.  Callables should be static class methods.  Example:
     *
     * return array('@^thumbnail:(\d+)$@', 'My_Photo_Thumbnailer::update');
     ***/
    protected $handlers = array();

    public function __construct()
    {
        $this->db = Framework_Database::getInstance();

        $fn = get_app_path("config/tasks.php");
        if (!is_readable($fn))
            throw new RuntimeException("Task routing table (config/tasks.php) not found.");

        $this->handlers = include $fn;
        if (!is_array($this->handlers)) {
            log_warning("taskq: routing table: %s", var_export($this->handlers, 1));
            throw new RuntimeException("Task routing table (config/tasks.php) must return an array.");
        }
    }

    public static function queue($command, $priority = 0, $attempts = 10)
    {
        $queue = new self;
        $db = $queue->db;

        try {
            $rows = $db->fetch("SELECT * FROM `{$queue->tableName}` WHERE `command` = ?", array($command));
            if ($rows) {
                $id = $rows[0]["id"];
                $db->query("UPDATE `{$queue->tableName}` SET `run_after` = 0, `attempts` = ?, `priority` = ? WHERE `id` = ?", array($attempts, $priority, $id));
                log_info("taskq: task %u (%s) rescheduled.", $id, $command);
            } else {
                $db->query("INSERT INTO `{$queue->tableName}` (`command`, `run_after`, `priority`, `attempts`) VALUES (?, ?, ?, ?)",
                    array($command, 0, $priority, $attempts));
                $task_id = $db->getLastInsertId();

                log_info("taskq: task %u (%s) added with priority=%d.", $task_id, $command, $priority);
            }
        } catch (Exception $e) {
            log_error("taskq: could not queue task '%s': %s: %s",
                $command, get_class($e), $e->getMessage());
        }
    }

    public static function exec($command)
    {
        $queue = new self;
        $queue->dispatch(array(
            "id" => 0,
            "command" => $command,
            ));
    }

    public static function queue_urgent($command)
    {
        return self::queue($command, 10);
    }

    public static function queue_lazy($command)
    {
        return self::queue($command, -10);
    }

    /**
     * Run the queue daemon.
     *
     * Performs all tasks, then waits for new ones.  Never returns.
     *
     * @return bool True on success (later), false if another instance was running.
     **/
    public function run()
    {
        log_debug("trying to run locked taskq");
        return Framework_Util::locked("taskq", array($this, "run_locked"));
    }

    public function run_locked()
    {
        $pid = getmypid();
        file_put_contents(DOC_ROOT . "/tmp/taskq.pid", $pid);

        // log_prefix(sprintf("taskq[%u]: ", getmypid()));

        set_time_limit(0);

        log_info("worker daemon started, stop with a kill signal.");

        while (true) {
            $task = $this->pickTask();
            if ($task) {
                $this->runTaskHandler($task);
            } else {
                sleep(1);
            }
        }
    }

    /**
     * Run task handler in a separate process.
     *
     * If the handler process returns with code 0, then the task is assumed
     * to have been successfully handled, and is deleted from the database.
     *
     * If the handler returns with code other than 0, then the task is re-scheduled,
     * if there are some attempts left.  Failed tasks with zero remaining attempts
     * are deleted.
     *
     * @param array $task Task properties.
     **/
    public function runTaskHandler(array $task)
    {
        $id = $task["id"];

        $script = $_SERVER["SCRIPT_FILENAME"];
        $command = "php -f {$script} taskq-runtask {$id}";

        log_debug("exec: %s", $command);

        // Re-schedule before execution.  This allows handlers to modify this later.
        $this->db->query("UPDATE `taskq` SET `run_after` = ?, `attempts` = ? WHERE `id` = ?", array(time() + 60, $task["attempts"] - 1, $id));

        $output = $rc = null;
        ob_start();
        exec($command, $output, $rc);
        ob_end_clean();

        if ($rc == 0) {
            log_info("task %u handled successfully, deleting.", $id);
            $this->deleteTask($id);
        } else {
            log_info("task %u (%s) handler failed with code %d.", $id, $task["command"], $rc);
            $this->db->query("DELETE FROM `taskq` WHERE `id` = ? AND `attempts` < 1", array($id));
            sleep(1);
        }
    }

    /**
     * Execute a task by id.
     *
     * This is called in a separate process.  The attempts counter was already
     * decreased by the master process, the run_after timestamp was set to
     * now + 60 seconds.  The task will be deleted from the database by the master
     * process if it succeeds or if the last attempt fails.
     *
     * Task handlers or disatchers could change run_after or increase attempts.
     *
     * @param string $taskId Task id to execute.
     **/
    public static function execById($taskId)
    {
        $db = Framework_Database::getInstance();

        $rows = $db->fetch("SELECT * FROM `taskq` WHERE `id` = ?", array($taskId));
        if (!$rows) {
            log_warning("task %u not found.", $taskId);
            return;
        }

        $task = array_merge(array(
            "id" => null,
            "command" => null,
            ), $rows[0]);

        $tq = new self;

        if (substr($task["command"], 0, 5) == "call:")
            $tq->dispatchClass($task);

        else
            $tq->dispatchRoute($task);
    }

    /**
     * Dispatch a task using the route table.
     **/
    protected function dispatchRoute(array $task)
    {
        $command = $task["command"];

        foreach ($this->handlers as $pattern => $func) {
            if (preg_match($pattern, $command, $m)) {
                if (false === strpos($func, "::")) {
                    if (!function_exists($func)) {
                        log_error("task %u (%s) has no handler: function %s does not exist, deleting.", $task["id"], $command, $func);
                        $this->deleteTask($task["id"]);
                        return;
                    }
                } else {
                    $parts = explode("::", $func);
                    if (!class_exists($parts[0])) {
                        log_error("task %u (%s) has no handler: class %s does not exist, deleting.", $task["id"], $command, $parts[0]);
                        $this->deleteTask($task["id"]);
                        return;
                    }
                }

                try {
                    $this->db->beginTransaction();
                    $args = array_slice($m, 1);
                    log_debug("calling %s with args=%s", $func, json_encode($args));
                    call_user_func_array($func, $args);

                    log_info("task %u (%s) finished.", $task["id"], $command);
                    $this->deleteTask($task["id"]);

                    $this->db->commit();

                    return;
                } catch (Exception $e) {
                    $this->db->rollback();

                    if ($task["attempts"] > 1) {
                        log_warning("task %u (%s) failed: %s -- will retry.", $task["id"], $command, $e->getMessage());
                    } else {
                        log_warning("task %u (%s) failed: %s -- fatal.", $task["id"], $command, $e->getMessage());
                    }

                    $this->deleteTask($task["id"]);
                    return;
                }
            }
        }

        log_warning("task %u (%s) has no handler, deleting.", $task["id"], $command);
        $this->deleteTask($task["id"]);
    }

    /**
     * Dispatch a task using the new object interface.
     *
     * Deletes the task on success, reschedules on failure.
     *
     * @param array $task Task description.
     * @return void
     **/
    protected function dispatchClass(array $task)
    {
        $args = explode(",", substr($task["command"], 5));
        $class = array_shift($args);

        if (!class_exists($class)) {
            log_warning("class %s does not exist -- cannot handle task %u", $class, $task["id"]);
            $this->deleteTask($task["id"]);
            return;
        }

        // Postpone before processing, to avoid endless looping.
        $this->db->query("UPDATE `taskq` SET `run_after` = ?, `attempts` = ? WHERE `id` = ?", array(time() + 60, $task["attempts"] - 1, $task["id"]));

        $this->db->beginTransaction();

        try {
            $h = new $class($task);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->handleConstructorFailure($task);
            exit(1);
        }

        try {
            $h->handle($args);
            log_info("task %u finished.", $task["id"]);
            $this->deleteTask($task["id"]);
            $this->db->commit();
            return;
        } catch (Exception $e) {
            $this->db->rollback();

            $h->retry($e->getMessage());
            if ($task["attempts"] <= 1)
                $this->deleteTask($task["id"]);

            exit(1);
        }
    }

    /**
     * Called when task handler constructor fails.
     **/
    protected function handleConstructorFailure(array $task)
    {
        log_error("task %u failed: %s -- class %s constructor failure, fatal, deleting.", $task["id"], $class, $e->getMessage());
        $this->deleteTask($task["id"]);
    }

    protected function getTaskHandler(array $task)
    {
        $command = $task["command"];

        // Direct class method call.
        if (0 === strpos($command, "call:")) {
            $args = explode(",", substr($command, 5));
            $class = array_shift($args);

            if (!class_exists($class)) {
                log_warning("class %s does not exist -- cannot handle task %u", $class, $task["id"]);
                return null;
            }

            $h = new $class($task);
            return array($h, $args);
        }

        // Routed tasks.
        foreach ($this->handlers as $pattern => $func) {
            if (preg_match($pattern, $command, $m)) {
                if (false === strpos($func, "::")) {
                    if (!function_exists($func)) {
                        log_warning("function %s does not exist -- cannot handle task %u", $func, $task["id"]);
                        return null;
                    }
                } else {
                    $parts = explode("::", $func);
                    if (!class_exists($parts[0])) {
                        log_warning("class %s does not exist -- cannot handle task %u", $parts[0], $task["id"]);
                        return null;
                    }
                }

                $args = array_slice($m, 1);
                return array($func, $args);
            }
        }

        log_warning("no handler for %s -- cannot handle task %u", $command, $task["id"]);
    }

    protected function pickTask()
    {
        $query = "SELECT * FROM `{$this->tableName}` WHERE `run_after` < ? AND `attempts` > 0 ORDER BY `priority` DESC, `id` LIMIT 1";
        $rows = $this->db->fetch($query, array(time()));
        if ($rows)
            return $rows[0];
    }

    protected function deleteTask($id)
    {
        $this->db->query("DELETE FROM `{$this->tableName}` WHERE `id` = ?", array($id));
    }

    protected static function unserializeArgs($args)
    {
        return explode(",", $args);
    }
}
