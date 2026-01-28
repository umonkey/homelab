<?php

class Framework_View
{
    protected $request;

    protected $_db = null;

    public function __construct(Framework_Request $request)
    {
        $this->request = $request;

        $path = $this->request->getPath();
        $this->logPrefix = "{$path}: ";
    }

    public function handle()
    {
        $args = func_get_args();

        switch ($this->request->getMethod()) {
        case "GET":
            $func = array($this, "onGet");
            break;
        case "HEAD":
            $func = array($this, "onHead");
            break;
        case "POST":
            $func = array($this, "onPost");
            break;
        default:
            return $this->fail("method not implimented");
        }

        return call_user_func_array($func, $args);
    }

    public function onGet()
    {
        throw new Framework_Errors_ServiceUnavailable("method GET not implimented");
    }

    public function onHead()
    {
        $args = func_get_args();
        $func = array($this, "onGet");
        return call_user_func_array($func, $args);
    }

    public function onPost()
    {
        throw new Framework_Errors_ServiceUnavailable("method POST not implimented");
    }

    protected function getArgs(array $defaults = array())
    {
        return array_merge($defaults, $this->request->getArgs());
    }

    protected function getArgs2(array $defaults)
    {
        $form = $this->request->getArgs();

        foreach ($defaults as $k => $v)
            if (array_key_exists($k, $form))
                $defaults[$k] = $form[$k];

        return $defaults;
    }

    /**
     * Returns filtered form fields.
     *
     * Only takes those fields from _POST for which a default was given.
     **/
    protected function getForm2(array $defaults)
    {
        $form = $this->request->getForm();

        foreach ($defaults as $k => $v)
            if (array_key_exists($k, $form))
                $defaults[$k] = $form[$k];

        return $defaults;
    }

    protected function getFiles($name)
    {
        return $this->request->files($name);
    }

    protected function isAjax()
    {
        return @$_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    }

    public function handle_exception(Exception $e)
    {
        if ($this->isAjax()) {
            $this->logException($e);

            $message = sprintf("%s: %s", get_class($e), $e->getMessage());
            return $this->sendJSON(array(
                "message" => $message,
                ));
        }

        // Handle in the router.
        throw $e;
    }

    public function prepare()
    {
    }

    public function complete()
    {
    }

    protected function sendHTML($html, $status = "200 OK")
    {
        return new Framework_Response($html, $status, array(
            "Content-Type" => "text/html; charset=utf-8",
            ));
    }

    protected function sendJSON(array $data)
    {
        return new Framework_Response(json_encode($data), "200 OK", array(
            "Content-Type" => "application/json",
            ));
    }

    protected function sendText($text, $status = "200 OK")
    {
        return new Framework_Response($text, $status, array(
            "Content-Type" => "text/plain; charset=utf-8",
            ));
    }

    protected function sendXML($text)
    {
        return new Framework_Response($text, "200 OK", array(
            "Content-Type" => "application/xml; charset=utf-8",
            ));
    }

    protected function sendScript($text)
    {
        return new Framework_Response($text, "200 OK", array(
            "Content-Type" => "application/javascript; charset=utf-8",
            ));
    }

    protected function sendFile($path, $type = "application/octet-stream", $status = "200 OK")
    {
        return new Framework_FileResponse($path, $status, array(
            "Content-Type" => $type,
            ));
    }

    protected function redirect($url, $status = "303 See Other")
    {
        Framework_Database::getInstance()->commit();

        if ($this->isAjax())
            return $this->sendJSON(array(
                "redirect" => $url,
                ));

        return new Framework_Response("Please go to {$url}", $status, array(
            "Content-Type" => "text/plain",
            "Location" => $url,
            ));
    }

    protected function seeOther($url)
    {
        return $this->redirect($url, "303 See Other");
    }

    protected function redirectPermanent($url)
    {
        return $this->redirect($url, "301 Moved Permanently");
    }

    protected function notfound($message = "Page not found.")
    {
        throw new Framework_Errors_NotFound($message);
    }

    protected function forbidden($message = "No access.")
    {
        throw new Framework_Errors_Forbidden($message);
    }

    protected function unavailable($message = "Service unavailable.")
    {
        throw new Framework_Errors_ServiceUnavailable($message);
    }

    protected function fail($message, $code = 500)
    {
        throw new RuntimeException($message);
    }

    public function __get($key)
    {
        switch ($key) {
            case "db":
                return Framework_Database::getInstance();

            default:
                throw new RuntimeException(sprintf("class %s has no property %s", get_class($this), $key));
        }
    }

    /**
     * Default method handler.
     *
     * Turn fatal errors into exceptions.
     **/
    public function __call($method, $args)
    {
        $class = get_class($this);
        throw new RuntimeException("method {$class}::{$method} not found");
    }
}
