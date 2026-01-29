<?php

declare(strict_types=1);

function debug(): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        header("HTTP/1.0 503 Debug");
        header("Content-Type: text/plain; charset=utf-8");
    }

    call_user_func_array("var_dump", func_get_args());
    print "---\n";

    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $stack = ob_get_clean();
    $stack = str_replace(dirname(__DIR__) . "/", "", $stack);
    print $stack;

    die();
}
