<?php

mb_internal_encoding("utf-8");

if (!defined("START_TIME"))
    define("START_TIME", microtime(true));

if (!empty($_SERVER["DOCUMENT_ROOT"]))
    define("DOC_ROOT", rtrim($_SERVER["DOCUMENT_ROOT"], "/"));
else
    define("DOC_ROOT", rtrim(getcwd(), "/"));

if (!defined("APP_ROOT"))
    define("APP_ROOT", dirname(__DIR__));

if (!ini_get("error_log"))
    ini_set("error_log", DOC_ROOT . "/php.log");
ini_set("log_errors", true);
ini_set("display_errors", false);
error_reporting(E_ALL);

require_once APP_ROOT . "/vendor/functions.php";
require_once APP_ROOT . "/vendor/autoload.php";

Framework_Logger::setup();
