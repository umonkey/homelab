<?php

class Framework_Debug
{
    public static function dump()
    {
        while (ob_get_level())
            ob_end_clean();

        ob_start();
        $args = func_get_args();
        call_user_func_array("var_dump", $args);

        $contents = ob_get_clean();

        error_log($contents);

        self::fail($contents);
    }

    public static function fail($message, $status = "503 Service Unavailable")
    {
        while (ob_get_level())
            ob_end_clean();

        if (!headers_sent()) {
            header("HTTP/1.0 " . $status);
            header("Content-Type: text/plain; charset=utf-8");
            header("Content-Length: " . strlen($message));
        }

        die($message);
    }
}
