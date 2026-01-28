<?php

ini_set("default_mimetype", "text/plain");
ini_set("default_charset", "utf-8");
ini_set("display_errors", 0);
error_reporting(E_ALL & ~(E_STRICT));

require_once __DIR__ . "/../vendor/bootstrap.php";

if (php_sapi_name() == "cli") {
    include APP_ROOT . "/cli.php";
} else {
    $request = Framework_Request::fromCGI();

    $response = Framework_Router::route($request);
    $response->send();
}
