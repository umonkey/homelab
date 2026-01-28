<?php
/**
 * Basic response class.
 *
 * Supports middleware, caching and compression.
 *
 * Usage example:
 *
 * $res = new Framework_Response($data, "200 OK", $headers, filemtime($data_fn));
 * $res->send();
 **/

class Framework_Response
{
    protected $_status = "200 OK";

    protected $_headers = array();

    protected $_body = null;

    /**
     * File modification time.  If set, 304 response code will be supported.
     **/
    protected $_mtime = null;

    /**
     * Default constructor.
     *
     * @param string $body Response contents.
     * @param string $status HTTP status, e.g. "200 OK".
     * @param array $headers Response headers, key-value.
     * @param integer $mtime Data modification time.  Enables caching.
     **/
    public function __construct($body = null, $status = "200 OK", array $headers = array(), $mtime = null)
    {
        $this->_status = $status;
        $this->_headers = $headers;
        $this->_mtime = $mtime;

        $this->setBody($body);
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function setHeaders(array $headers)
    {
        $this->_headers = $headers;
    }

    public function setHeader($header, $value)
    {
        $this->_headers[$header] = $value;
    }

    public function getBody()
    {
        $body = $this->_body;

        $parts = explode(";", $this->_headers["Content-Type"]);
        $ct = $parts[0];

        $mw = Framework_Config::get("middleware", array());
        foreach ($mw as $handler)
            $body = call_user_func_array($handler, array($ct, $body));

        return $body;
    }

    public function setBody($body)
    {
        $this->_body = $body;
    }

    public function send()
    {
        if (headers_sent())
            throw new RuntimeException("response headers already sent");

        $if_none_match = @$_SERVER["HTTP_IF_NONE_MATCH"];
        $if_modified_since = @$_SERVER["HTTP_IF_MODIFIED_SINCE"];
        $method = $_SERVER["REQUEST_METHOD"];

        $body = $this->getBody();
        $hash = md5($body);

        $status = $this->getStatus();

        $headers = $this->getHeaders();
        $headers["Content-Length"] = strlen($body);

        // Support caching if file modification time is set.
        if ($this->_mtime) {
            $headers["ETag"] = '"' . $hash . '"';
            if ($if_none_match == $headers["ETag"]) {
                $status = "304 Not Modified";
                $body = null;
                $headers["Content-Length"] = null;
            }

            $headers["Last-Modified"] = gmdate("D, d M Y H:i:s", $this->_mtime) . " GMT";
            if ($if_modified_since == $headers["Last-Modified"]) {
                $status = "304 Not Modified";
                $body = null;
                $headers["Content-Length"] = null;
            }
        }

        if ($method == "HEAD")
            $body = null;

        // Compression.
        if ($body !== null)
            $this->compress($body, $headers);

        if (function_exists("memory_get_usage"))
            $headers["X-Memory-Usage"] = sprintf("%.3f MB", memory_get_usage() / 1048576);

        header("HTTP/1.0 " . $status);
        foreach ($headers as $k => $v) {
            if ($v !== null)
                header("{$k}: {$v}");
        }

        $parts = explode(" ", $status);
        if ($parts[0] >= 400)
            log_debug("Response: code=%s size=%u uri=%s method=%s",
                $parts[0], strlen($body), $_SERVER["REQUEST_URI"], $method);

        echo $body;
    }

    /**
     * Compress response data.
     **/
    protected function compress(&$body, array &$headers)
    {
        $accept_encoding = @$_SERVER["HTTP_ACCEPT_ENCODING"];
        $accept = preg_split('@,\s*@', $accept_encoding, -1, PREG_SPLIT_NO_EMPTY);

        $can_gzip = function_exists("gzencode");
        $can_deflate = function_exists("gzencode") and function_exists("gzcompress");

        if ($can_gzip and in_array("gzip", $accept)) {
            $headers["Content-Encoding"] = "gzip";
            $slen = strlen($body);
            $body = gzencode($body, 6, FORCE_GZIP);
            $headers["Content-Length"] = $dlen = strlen($body);
            $headers["X-Compression"] = sprintf("src=%u saved=%u ratio=%f", $slen, $slen - $dlen, $slen / $dlen);
        }

        elseif ($can_deflate and in_array("deflate", $accept)) {
            $headers["Content-Encoding"] = "deflate";
            $body = gzcompress($body, 6);
            $headers["Content-Length"] = $dlen = strlen($body);
            $headers["X-Compression"] = sprintf("src=%u saved=%u ratio=%f", $slen, $slen - $dlen, $slen / $dlen);
        }
    }
}
