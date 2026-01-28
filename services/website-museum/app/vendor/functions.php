<?php
/**
 * Put your global functions here.
 **/

function get_doc_path($path)
{
    return DOC_ROOT . "/" . $path;
}


function get_app_path($path)
{
    return APP_ROOT . "/" . $path;
}


function get_temp_path($path)
{
    return DOC_ROOT . "/tmp/" . $path;
}


function write_file($path, $data)
{
    if (!is_readable($path)) {
        $folder = dirname($path);
        if (!file_exists($folder)) {
            $res = @mkdir($folder, 0775, true);
            if (!$res and !is_dir($foldeR))
                throw new RuntimeException("could not create folder {$folder}");
        }
    }

    $res = @file_put_contents($path, $data);
    if ($res === false)
        throw new RuntimeException("could not write to {$path}");

    log_debug("wrote %u bytes to %s", strlen($data), $path);
}


function debug()
{
    while (ob_get_level())
        ob_end_clean();

    if (!headers_sent()) {
        header("HTTP/1.1 503 Debug Output");
        header("Content-Type: text/plain; charset=utf-8");
    }

    ob_start();
    if (isset($_SERVER["HTTP_HOST"]))
        printf("http://%s%s\n---\n", $_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"]);

    if (defined("START_TIME"))
        printf("Duration: %f seconds\n", microtime(true) - START_TIME);

    printf("---\n");
    $args = func_get_args();

    call_user_func_array("var_dump", $args);
    $output = ob_get_clean();

    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $bt = ob_get_clean();

    $bt = str_replace(realpath($_SERVER["DOCUMENT_ROOT"]) . "/", "", $bt);
    $bt = preg_replace('@phar://.+\.phar/@', 'src/', $bt);

    $output .= $bt;

    $output .= sprintf("---\nphp version %s\nmemory used: %.3f MB", phpversion(), memory_get_usage() / 1048576);

    // error_log($output);
    die($output);
}


function debug2($key)
{
    if ($key !== @$_GET["debug"])
        return;

    $args = func_get_args();
    $args = array_slice($args, 1);
    return call_user_func_array("debug", $args);
}
