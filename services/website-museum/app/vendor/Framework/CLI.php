<?php

class Framework_CLI
{
    public static function handle(array $args)
    {
        if (count($args) < 2)
            return;

        $command = $args[1];

        if ($command == "taskq") {
            $tq = new Framework_TaskQueue;
            if (!$tq->run())
                die("Task queue runner is already running.\n");
            die("Task queue runner exited.\n");
        }

        elseif ($command == "taskq-runtask") {
            if (function_exists("log_prefix")) {
                define("TASKQ_PID", getmypid());
                log_prefix("taskq[" . getmypid() . "]: ");
            }

            $taskId = $args[2];
            Framework_TaskQueue::execById($taskId);
            exit(0);
        }

        elseif ($command == "static") {
            // TODO
            exit(0);
        }

        elseif ($command == "activate") {
            $path = Phar::running(false);

            $loader = "<?php \$_phar = '{$path}';\n";
            $loader .= "if (!is_readable(\$_phar)) die('Phar file not found: ' . \$_phar);\n";
            $loader .= "require \$_phar;\n";

            file_put_contents("index.php", $loader);

            printf("Wrote new index.php\n");
            exit(0);
        }
    }
}
