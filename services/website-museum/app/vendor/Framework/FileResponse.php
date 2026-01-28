<?php

class Framework_FileResponse extends Framework_Response
{
    protected $_path;

    public function __construct($filePath, $status, array $headers = array())
    {
        if (!is_readable($filePath)) {
            log_error("file %s is not readable", $filePath);
            throw new RuntimeException("file does not exist");
        }

        $this->_status = $status;
        $this->_headers = $headers;
        $this->_path = $filePath;
        $this->_mtime = filemtime($filePath);
    }

    public function send()
    {
        if (headers_sent())
            throw new RuntimeException("response headers already sent");

        $if_none_match = @$_SERVER["HTTP_IF_NONE_MATCH"];
        $if_modified_since = @$_SERVER["HTTP_IF_MODIFIED_SINCE"];

        $hash = md5_file($this->_path);
        $total_length = filesize($this->_path);

        $status = $this->getStatus();
        $headers = $this->getHeaders();
        $headers["Content-Length"] = $total_length;
        $headers["Accept-Range"] = "bytes";

        $f = fopen($this->_path, "rb");
        $send_length = $total_length;

        if ($range = @$_SERVER["HTTP_RANGE"] and 0 === strpos($range, "bytes=")) {
            $range = substr($range, 6);
            // log_debug("download: range = %s", $range);
            if (count($parts = explode("-", $range)) == 2) {
                $start = intval($parts[0]);
                $end = $parts[1] ? intval($parts[1]) : $total_length - 1;
                $end = min($total_length - 1, $end);

                $send_length = $end - $start + 1;

                if ($start > 0 or $send_length != $total_length)
                    fseek($f, $start);

                $status = "206 Partial Content";
                $headers["Content-Range"] = "bytes {$start}-{$end}/{$total_length}";
                $headers["Content-Length"] = $send_length;

                // log_debug("download: sending %u bytes from %u", $send_length, $start);
            } else {
                // log_debug("download: sending whole file -- bad range");
            }
        } else {
            // log_debug("download: sending whole file -- no range requested");
        }

        // Support caching if file modification time is set.
        if ($this->_mtime and $send_length == $total_length) {
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

        if (function_exists("memory_get_usage"))
            $headers["X-Memory-Usage"] = sprintf("%.3f MB", memory_get_usage() / 1048576);

        $this->sendHeader("HTTP/1.0 " . $status);
        foreach ($headers as $k => $v) {
            if ($v !== null)
                $this->sendHeader("{$k}: {$v}");
        }

        if ($status != "200 OK")
            log_debug("Sending %u bytes [%s].", $send_length, $this->getStatus());

        while ($send_length > 0) {
            $portion = min(4096, $send_length);
            $data = fread($f, $portion);
            echo $data;
            $send_length -= 4096;
        }

        fclose($f);
    }

    protected function sendHeader($h)
    {
        // log_debug("download: response header: %s", $h);
        header($h);
    }
}
