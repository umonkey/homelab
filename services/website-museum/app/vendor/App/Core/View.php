<?php

class App_Core_View extends Framework_View
{
    use App_PackerT;

    protected $logger = null;

    protected $user;

    /**
     * Массив с данными для скриптов.
     *
     * Структура массива произвольна.  Он будет выдан шаблону через переменную jsdata,
     * как его использовать -- решает шаблон и скрипты.
     **/
    public $jsdata = array();

    protected $scripts = array();

    protected $session;

    public function __construct(Framework_Request $request)
    {
        parent::__construct($request);

        $this->logger = new Framework_Logger;

        $this->user = $this->getUser();
    }

    public function handle_exception(Exception $e)
    {
        if ($this->isAjax()) {
            return $this->sendJSON(array(
                "message" => $e->getMessage(),
                ));
        }

        if ($e instanceof Framework_Errors_NotFound) {
            $template = "notfound.twig";
            $status = "404 Not Found";
        } elseif ($e instanceof Framework_Errors_Forbidden) {
            $template = "forbidden.twig";
            $status = "403 Forbidden";
        } elseif ($e instanceof Framework_Errors_Unauthorized) {
            return $this->sendPage("unauthorized.twig", [], "401 Unauthorized");
        } else {
            $template = "error.twig";
            $status = "500 Internal Server Error";
            log_exception($e);
        }

        try {
            return $this->sendPage($template, array(
                "message" => $e->getMessage(),
                ), $status);
        } catch (Exception $e) {
            log_exception($e);
            $message = sprintf("Error rendering %s: %s", $template, $e->getMessage());
            return new Framework_Response($message, $status, array(
                "Content-Type" => "text/plain; charset=utf-8",
                ));
        }
    }

    protected function sendPage($templateName, array $data, $status = "200 OK")
    {
        if (!empty($data["breadcrumbs"]))
            $this->addBreadcrumbsJSLD($data["breadcrumbs"]);

        $data["assets"] = $this->getAssets();

        $tmp = explode("?", $_SERVER["REQUEST_URI"]);
        $data["page_path"] = $tmp[0];
        $this->jsdata["page_path"] = $tmp[0];

        $data["args"] = array(
            "get" => $_GET,
            "post" => $_POST,
            );

        $data["is_admin"] = $this->isAdmin();

        if ($user = $this->getUser()) {
            $data["current_user"] = $user->forTemplate();
        }

        $data["more_scripts"] = implode("", $this->scripts);

        if ($jsdata = $this->jsdata) {
            $jsdata["version"] = 1;
            $data["jsdata"] = $jsdata;

            $_jsdata = json_encode($jsdata);
            $script = "<script type='text/javascript'>window.jsdata = {$_jsdata};</script>";

            $data["more_scripts"] = $script . $data["more_scripts"];
        }

        $tplPath = get_app_path("templates/{$templateName}");

        debug2("tpl", $tplPath, $data);

        $t = new App_Core_TwigTemplate($tplPath);
        $html = $t->render($data);
        $html = App_Common::stripSpaces($html);
        return $this->sendHTML($html, $status);
    }

    protected function getForm(array $defaults = array())
    {
        return array_merge($defaults, $this->request->getForm());
    }

    protected function sessionRead(array $defaults = null)
    {
        if ($defaults === null)
            $defaults = array(
                "is_admin" => false,
                );

        if (!$this->session)
            $this->session = new Framework_Session($defaults);

        return $this->session;
    }

    /**
     * Returns user profile.
     *
     * If the user is not logged in or is disabled -- throws 401.
     *
     * @return Nebo_Models_User User profile.
     **/
    protected function requireUser()
    {
        if (!($user = $this->getUser()))
            throw new Framework_Errors_Unauthorized("authentication required");

        if (!$user->enabled)
            throw new Framework_Errors_Forbidden("account disabled");

        return $user;
    }

    protected function requireAdmin()
    {
        $s = $this->sessionRead();

        if ($s["vk"] === null)
            throw new Framework_Errors_Unauthorized("Эта функция доступна только администраторам.");

        if (!$s["is_admin"])
            throw new Framework_Errors_Forbidden("Эта функция доступна только администраторам.");

        return true;
    }

    protected function getUser()
    {
        $session = $this->sessionRead();
        if (empty($session["user_id"]))
            return null;

        $user = App_Models_User::getById($session["user_id"], false);
        if ($user and $user->enabled)
            return $user;
    }

    protected function getAssets()
    {
        $assets = array();

        $files = glob(get_doc_path("*"));
        foreach ($files as $fn) {
            if (preg_match('@\.(css|js)$@', $fn)) {
                $name = basename($fn);
                $key = str_replace(".", "_", $name);
                $etag = sprintf("%x-%x", filemtime($fn), filesize($fn));
                $assets[$key] = "/{$name}?etag=" . $etag;
            }
        }

        return $assets;
    }

    protected function forTemplate(Framework_ModelIterator $items)
    {
        $res = array();

        foreach ($items as $item)
            $res[] = $item->forTemplate();

        return $res;
    }

    protected function config($key, $default = null)
    {
        return Framework_Config::get($key, $default);
    }

    protected function isAdmin()
    {
        $s = $this->sessionRead();
        return (bool)$s["is_admin"];
    }

    public function addScript($name, $code)
    {
        if (false === strpos($code, '<script'))
            throw new RuntimeException("addScript expects a HTML/JavaScript snippet");
        $this->scripts[$name] = $code;
    }

    public function addDefaultScript($name, $code)
    {
        if (false === strpos($code, '<script'))
            throw new RuntimeException("addScript expects a HTML/JavaScript snippet");
        if (empty($this->scripts[$name]))
            $this->scripts[$name] = $code;
    }

    protected function addJSONLD($name, array $data)
    {
        $sdata = json_encode($data);
        $script = "<script type='application/ld+json'>{$sdata}</script>";
        $this->addScript($name, $script);
    }

    protected function addBreadcrumbsJSLD(array $items)
    {
        $code = array(
            "@context" => "http://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => array(),
            );

        foreach (array_values($items) as $idx => $item) {
            $code["itemListElement"][] = array(
                "@type" => "ListItem",
                "position" => $idx + 1,
                "item" => array(
                    "@id" => $item["link"],
                    "name" => $item["label"],
                    ),
                );
        }

        $this->addJSONLD("breadcrumbs", $code);
    }

    protected function logAction($message, array $props)
    {
        $message = $this->formatMessage($message, $props);

        $row = [
            "timestamp" => time(),
            "message" => $message,
            "ip_addr" => $_SERVER["REMOTE_ADDR"],
            "user_agent" => $_SERVER["HTTP_USER_AGENT"],
            "user" => null,  // FIXME
        ];

        $row = $this->packLog($row);

        $db = Framework_Database::getInstance();
        $db->insert("log", $row);
    }

    protected function formatMessage($message, array $props)
    {
        $repl = [];
        foreach ($props as $k => $v) {
            $k = '{' . $k . '}';
            if (is_array($v))
                $v = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $repl[$k] = $v;
        }

        $message = strtr($message, $repl);

        return $message;
    }
}
