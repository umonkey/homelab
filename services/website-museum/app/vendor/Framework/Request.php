<?php

class Framework_Request
{
    protected $_method = null;

    protected $_get = array();

    protected $_post = array();

    protected $_files = array();

    protected $_path = null;

    protected $_qs = null;

    protected $_protocol = null;

    protected $_host = null;

    public static function fromCGI()
    {
        $r = new static;
        $r->_method = @$_SERVER["REQUEST_METHOD"];

        $parts = explode("?", @$_SERVER["REQUEST_URI"]);
        $r->_path = $parts[0];

        $r->_qs = isset($_SERVER["QUERY_STRING"])
            ? $_SERVER["QUERY_STRING"] : null;

        $r->_get = (array)@$_GET;
        $r->_post = (array)@$_POST;
        $r->_files = @$_FILES;
        $r->_host = @$_SERVER["HTTP_HOST"];

        if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]))
            $r->_proto = $_SERVER["HTTP_X_FORWARDED_PROTO"];
        else
            $r->_proto = "http";

        return $r;
    }

    public static function fromPath($path, $method = "GET", $host = "localhost")
    {
        $r = new static;
        $r->_method = $method;

        $parts = explode("?", $path);
        $r->_path = $parts[0];
        $r->_qs = isset($parts[1]) ? $parts[1] : null;

        $r->_get = Artwall_Util::parse_qs($r->_qs);
        $r->_post = array();
        $r->_files = array();
        $r->_host = $host;

        $r->_proto = "http";

        return $r;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getQueryString()
    {
        return $this->_qs;
    }

    public function arg($name, $default = null)
    {
        if (array_key_exists($name, $this->_post))
            return $this->_post[$name];

        if (array_key_exists($name, $this->_get))
            return $this->_get[$name];

        return $default;
    }

    public function file($name)
    {
        if (array_key_exists($name, $this->_files))
            return $this->_files[$name];
        return null;
    }

    public function files($name = null)
    {
        if (empty($this->_files))
            return array();

        $files = array();

        foreach ($this->_files as $field => $props) {
            if (is_array($props["name"])) {
                $keys = array_keys($props["name"]);

                foreach ($props as $prop => $vals) {
                    foreach ($vals as $k => $v) {
                        $files[$field][$k][$prop] = $v;
                    }
                }
            } else {
                $files[$field] = array($props);
            }
        }

        if ($name !== null) {
            if (isset($files[$name]))
                return $files[$name];
            return array();
        }

        return $files;
    }

    public function getArgs()
    {
        return $this->_get;
    }

    public function getHost()
    {
        return $this->_host;
    }

    public function getProtocol()
    {
        return $this->_proto;
    }

    public function getForm(array $defaults = array())
    {
        return array_merge($defaults, $this->_post);
    }
}
