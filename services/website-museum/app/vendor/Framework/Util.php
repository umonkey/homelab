<?php
/**
 * Some handy utility functions.
 **/

class Framework_Util
{
    public static function fetchURL($url)
    {
        // log_debug("fetching <%s>", $url);

        $res = array(
            "status" => null,
            "status_text" => null,
            "headers" => array(),
            "data" => @file_get_contents($url, false),
            );

        foreach ($http_response_header as $h) {
            if (preg_match('@^HTTP/[0-9.]+ (\d+) (.*)$@', $h, $m)) {
                $res["status"] = $m[1];
                $res["status_text"] = $m[2];
            } else {
                $parts = explode(":", $h, 2);
                $k = strtolower(trim($parts[0]));
                $v = trim($parts[1]);
                $res["headers"][$k] = $v;
            }
        }

        return $res;
    }

    public static function fetch($url)
    {
        $res = self::fetchURL($url);
        return $res["data"];
    }

    /**
     * Run code in a single process.
     *
     * Example:
     *
     * $res = Framework_Util::locked("tmp/wtf.lock", wtf);
     * if (!$res) die("Another worker is running.");
     *
     * @param string $fn Lock file name (tmp/wtf.lock)
     * @param callable $func What to call when locked.
     * @return bool true, if the lock was successful.
     **/
    public static function locked($name, $func)
    {
        $folder = Framework_Config::get("tmpdir", getcwd() . "/tmp");
        $fn = sprintf("%s/%s.lock", $folder, $name);
        if (!($f = fopen($fn, "w+")))
            throw new RuntimeException("could not open lock file {$fn}");

        $res = flock($f, LOCK_EX | LOCK_NB);
        if ($res) {
            try {
                $func();
                fclose($f);
                return $res;
            } catch (Exception $e) {
                fclose($f);
                throw $e;
            }
        }
    }

    /**
     * Perform an HTTP POST request using built in functions.
     *
     * @param string $url Where to post.
     * @param string $data What to post.
     * @param array $headers Additional HTTP headers.
     * @param string $method Request method, defaults to POST.
     * @return string Response body.
     **/
    public static function post($url, $data, array $headers = array(), $method = "POST")
    {
        if (is_array($data)) {
            if ($data = self::buildURL("", $data))
                $data = substr($data, 1);
            $headers["Content-Type"] = "application/x-www-form-urlencoded";
        }

        if (!is_string($data) and $data !== null)
            throw new RuntimeException("post data must be a string");

        $h = "";
        foreach ($headers as $k => $v)
            $h .= "{$k}: {$v}\r\n";

        $context = stream_context_create($ctx = array(
            "http" => array(
                "method" => $method,
                "header" => $h,
                "content" => $data,
                "ignore_errors" => true,
                ),
            ));

        $res = array(
            "status" => null,
            "status_text" => null,
            "headers" => array(),
            "data" => file_get_contents($url, false, $context),
            );

        foreach ($http_response_header as $h) {
            if (preg_match('@^HTTP/[0-9.]+ (\d+) (.*)$@', $h, $m)) {
                $res["status"] = $m[1];
                $res["status_text"] = $m[2];
            } else {
                $parts = explode(":", $h, 2);
                $k = strtolower(trim($parts[0]));
                $v = trim($parts[1]);
                $res["headers"][$k] = $v;
            }
        }

        return $res;
    }

    public static function buildURL($base, array $args)
    {
        $qs = array();
        foreach ($args as $k => $v)
            $qs[] = $k . "=" . urlencode($v);

        $url = $base;
        if ($qs)
            $url .= "?" . implode("&", $qs);

        return $url;
    }

    public static function dump()
    {
        $args = func_get_args();
        Framework_Errors_Dump::dump($args);
    }
}
