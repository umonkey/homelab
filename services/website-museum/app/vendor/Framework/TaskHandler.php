<?php
/**
 * Basic task handler class.
 *
 * Implement a subclass, then call the queue method.  When time comes,
 * the handle() method will be called with all arguments passed back.
 **/

abstract class Framework_TaskHandler
{
    protected $task;

    public function __construct(array $task)
    {
        $this->task = $task;
    }

    public static function queue(array $args = array(), $priority = 0)
    {
        if (!function_exists("get_called_class"))
            throw new RuntimeException("php version too old");

        $className = get_called_class();
        array_unshift($args, $className);
        $args = self::serializeArgs($args);

        $command = sprintf("call:%s", $args);
        Framework_TaskQueue::queue($command, $priority);
    }

    protected static function serializeArgs(array $args)
    {
        $res = array();
        foreach ($args as $arg) {
            if ($arg === true)
                $res[] = "1";
            elseif ($arg === false)
                $res[] = "0";
            elseif ($arg === null)
                $res[] = "";
            else
                $res[] = strval($arg);
        }

        return implode(",", $res);
    }

    abstract public function handle(array $args);

    /**
     * Called to retry the task.
     *
     * This is called when the task failed.  The attempt counter had already been decreased
     * and a default delay of 60 seconds was added before the task handler was called.
     * This is necessary to prevent handlers with fatal errors from running forever.
     *
     * The handler might adjust the run_after property in the database (using an SQL query),
     * log something or send email, then return.  No need to delete the task if the attempts
     * are over -- this is done automatically.
     *
     * This is called outside of the transaction, so email sending should work.
     *
     * @param string $message Error message, retry reason.
     **/
    public function retry($message)
    {
        if ($this->task["attempts"] > 1) {
            $this->delay(60);
        } else {
            // TODO: email the developer.
            if ($message)
                log_warning("task %u failed too many times, will not retry: %s.", $this->task["id"], $message);
            else
                log_warning("task %u failed too many times, will not retry.", $this->task["id"]);
        }
    }

    protected function delay($seconds, $reason = null)
    {
        if ($reason)
            log_info("task %u will retry in %u seconds: %s.", $this->task["id"], $seconds, $reason);
        else
            log_info("task %u will retry in %u seconds.", $this->task["id"], $seconds);

        $db = Framework_Database::getInstance();
        $db->query("UPDATE `taskq` SET `run_after` = ? WHERE `id` = ?", array(time() + $seconds, $this->task["id"]));
    }
}
