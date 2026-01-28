<?php

class Framework_Router
{
    private static $routes = null;

    public static function route(Framework_Request $request)
    {
        try {
            $request_uri = $request->getPath();
            $request_method = $request->getMethod();

            $routes = self::getRoutes();
            if (!is_array($routes))
                throw new Framework_Errors_ServiceUnavailable("Bad routes table.");

            foreach ($routes as $idx => $route) {
                if (count($route) != 3) {
                    log_warning("router: bad route #{$idx} format, must have 3 items.");
                    continue;
                }

                list($pattern, $method, $className) = $route;
                if ($method == $request_method or $method == "*" or ($method == "GET" and $request_method == "HEAD")) {
                    if (preg_match($pattern, $request_uri, $m)) {
                        return static::handleRequest($className,
                            array_slice($m, 1), $request);
                    }
                }
            }

            throw new Framework_Errors_NotFound("Page not found.");
        } catch (Exception $e) {
            return static::handleException($e);
        }
    }

    protected static function handleException($e)
    {
        if (function_exists("log_exception") and $e->getCode() >= 500)
            log_exception($e);

        $codes = array(
            400 => "400 Bad Request",
            403 => "403 Forbidden",
            404 => "404 Not Found",
            405 => "405 Method Not Allowed",
            );

        $code = $e->getCode();
        if (array_key_exists($code, $codes))
            $status = $codes[$code];
        else
            $status = "500 Internal Server Error";

        return new Framework_Response($e->getMessage(), $status, array(
            "Content-Type" => "text/plain; charset=utf-8",
            ));
    }

    protected static function handleRequest($className, $args, Framework_Request $request)
    {
        if (!class_exists($className))
            throw new Framework_Errors_ServiceUnavailable("Class {$className} not found, unable to handle request.");

        $view = new $className($request);
        if (!($view instanceof Framework_View))
            throw new RuntimeException("Class {$className} is not a view.");

        try {
            $view->prepare();

            $response = call_user_func_array(array($view, "handle"), $args);

            $view->complete();
        } catch (Exception $e) {
            $func = array($view, "handle_exception");
            $args = array($e);
            $response = call_user_func_array($func, $args);
        }

        if (is_string($response))
            $response = new Framework_Response($response, array(
                "Content-Type" => "text/plain; charset=utf-8",
                ), "200 OK");

        if (!($response instanceof Framework_Response))
            throw new RuntimeException("Class {$className} did not return a valid response.");

        return $response;
    }

    protected static function getRoutes()
    {
        if (self::$routes === null) {
            self::$routes = include APP_ROOT . "/config/routes.php";
            if (false === self::$routes)
                throw new RuntimeException("Routes not defined, please create config/routes.php");
        }

        return self::$routes;
    }
}

// vim: set ts=4 sts=4 sw=4 et:
