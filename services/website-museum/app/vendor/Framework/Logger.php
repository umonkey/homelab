<?php
/**
 * Simple logger class.
 *
 * This actually defines a set of handy functions: log_debug, log_info,
 * log_warning, log_error and log_exception.  Exception logging writes
 * error message and a stack trace using log_error.
 *
 * Possible enhancements: need to override log_exception, e.g. to email
 * stack traces to a developer.  This cannot be universally made, because
 * email sending differs in projects.  Perhaps there needs to be a global
 * or static variable which would hold a class instance.  You would override
 * it, initialize your copy, etc.
 *
 **/

class Framework_Logger
{
    protected static $instance = null;

    /**
     * Text to prefix all messages with.
     **/
    protected $prefix = null;

    /**
     * Logger initialization.
     *
     * Currently does nothing.  Just call this to load the functions.
     *
     * @return void
     **/
    public static function setup()
    {
        ini_set("log_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
    }

    public static function getInstance()
    {
        if (null === static::$instance)
            static::$instance = new static;
        return static::$instance;
    }

    /**
     * Write a message to the log file.
     *
     * @param string $prefix Some text to prepend the message with.
     * @param array $args Message format string and args, passed to sprintf.
     * @return void
     **/
    public function logMessage($prefix, array $args)
    {
        $message = call_user_func_array("sprintf", $args);

        if ($this->prefix)
            $prefix = $this->prefix . $prefix;

        $text = call_user_func_array("sprintf", $args);
        foreach (explode("\n", $text) as $line) {
            if ($line = rtrim($line))
                error_log($prefix . $line);
        }
    }

    public function logException(Exception $e)
    {
        if (isset($_SERVER["REQUEST_URI"])) {
            log_error("%s: %s [code=%s; uri=%s]",
                get_class($e), $e->getMessage(), $e->getCode(), $_SERVER["REQUEST_URI"]);
        } else {
            log_error("%s: %s [code=%s]",
                get_class($e), $e->getMessage(), $e->getCode());
        }

        $stack = $e->getTraceAsString();

        $root = realpath($_SERVER["DOCUMENT_ROOT"]);
        $stack = str_replace($root . "/", "", $stack);

        $stack = preg_replace('@phar://.+\.phar@', 'src', $stack);

        $this->logMessage("ERR: ", array("%s", $stack));
    }
}


function log_error()
{
    $args = func_get_args();
    Framework_Logger::getInstance()->logMessage("ERR: ", $args);
}


function log_warning()
{
    $args = func_get_args();
    Framework_Logger::getInstance()->logMessage("WRN: ", $args);
}


function log_info()
{
    $args = func_get_args();
    Framework_Logger::getInstance()->logMessage("INF: ", $args);
}


function log_debug()
{
    $args = func_get_args();
    Framework_Logger::getInstance()->logMessage("DBG: ", $args);
}


function log_action()
{
    $args = func_get_args();
    Framework_Logger::getInstance()->logMessage("ACT: ", $args);
}


function log_exception(Exception $e)
{
    Framework_Logger::getInstance()->logException($e);
}
