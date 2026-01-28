<?php
/**
 * Static file handler.
 *
 * Enabled caching and compressing of pre-generated responses.  Useful when
 * running from PHAR files or when the web server doesn't handle static files
 * (wtf).
 *
 * Use directly in the routing file, e.g.:
 *
 * array('@^/(static/.*)$@', 'GET', 'Framework_StaticFile'),
 **/

class Framework_StaticFile extends Framework_View
{
    public function handle()
    {
        $headers = array();

		if (!defined("DOC_ROOT"))
			throw new RuntimeException("DOC_ROOT not defined");

        $path = DOC_ROOT . "/" . func_get_arg(0);
        if (!$path) {
            $status = "404 Not Found";
            $headers["Content-Type"] = "text/plain; charset=utf-8";
            $data = "File not found.";
            $mtime = null;
        } else {
            $status = "200 OK";
            $headers["Content-Type"] = $this->getType($path);
            $data = file_get_contents($path);
            $mtime = filemtime($path);
        }

        return new Framework_Response($data, $status, $headers, $mtime);
    }

    protected function getType($path)
    {
        $pi = pathinfo($path, PATHINFO_EXTENSION);
        switch ($pi) {
        case "png":
            return "image/png";
        case "jpg":
            return "image/jpeg";
        case "gif":
            return "image/gif";
        case "js":
            return "application/javascript";
        case "css":
            return "text/css";
        case "xml":
            return "application/xml";
        case "json":
            return "application/javascript";
        }

        $t = @mime_content_type($path);
        if ($t !== false)
            return $t;

        return "application/octet-stream";
    }
}
