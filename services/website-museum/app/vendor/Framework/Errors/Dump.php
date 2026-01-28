<?php
/**
 * Exception used to dump some data to the client.
 **/

class Framework_Errors_Dump extends Exception
{
    public static function dump(array $args)
    {
        $is_ajax = @$_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

        while (ob_get_level())
            ob_end_clean();

        ob_start();
        if (isset($_SERVER["HTTP_HOST"]))
            printf("http://%s%s\n---\n", $_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"]);

        if (defined("START_TIME"))
            printf("Duration: %f seconds\n", microtime(true) - START_TIME);

        printf("---\n");

        call_user_func_array("var_dump", $args);
        $output = ob_get_clean();

        ob_start();
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $bt = ob_get_clean();

        $bt = str_replace(realpath($_SERVER["DOCUMENT_ROOT"]) . "/", "", $bt);
        $bt = preg_replace('@phar://.+\.phar/@', 'src/', $bt);

        $output .= $bt;

        $output .= sprintf("---\nphp version %s\nmemory used: %.3f MB", phpversion(), memory_get_usage() / 1048576);

        throw new self($output);
    }
}
