<?php

class Framework_TwigTemplate
{
    protected $environment;

    protected $template;

    public function __construct($filePath)
    {
        if (!is_readable($filePath))
            throw new Framework_Errors_TemplateNotFound($filePath);

        $root = dirname($filePath);
        $loader = new Twig_Loader_Filesystem($root);

        $hash = md5($root . "/" . __FILE__);
        $twig = new Twig_Environment($loader, array(
            "cache" => __DIR__ . "/../../data/twig_cache/{$hash}",
            "auto_reload" => true,
            ));

        $this->environment = $twig;

        $this->prepare();

        $this->template = $twig->loadTemplate(basename($filePath));
    }

    public function render(array $data = array())
    {
        $defaults = array();
        if (isset($_SERVER["HTTP_HOST"]))
            $defaults["host"] = $_SERVER["HTTP_HOST"];
        if (isset($_SERVER["REQUEST_URI"])) {
            $tmp = explode("?", $_SERVER["REQUEST_URI"]);
            $defaults["page_path"] = reset($tmp);
        }

        $data = array_merge($defaults, $data);

        if ($tmp = Framework_Config::get("template_defaults"))
            $data = array_merge($tmp, $data);

        return $this->template->render($data);
    }

    /**
     * Set up additional filters here (in a subclass).
     **/
    protected function prepare()
    {
        // http://twig.sensiolabs.org/doc/advanced.html#filters

        $this->environment->addFilter(new Twig_SimpleFilter("phone", function ($string) {
            $phone = preg_replace('@[^0-9]+@', '', $string);

            if (preg_match('@7(\d\d\d)(\d\d\d)(\d\d)(\d\d)@', $phone, $m))
                return "+7 ({$m[1]}) {$m[2]}-{$m[3]}-{$m[4]}";

            return $string;
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("datetime", function ($string) {
            if (empty($string))
                return "";

            $p = date_parse_from_format("Y-m-d H:i:s", $string);
            $res = sprintf("%02u.%02u.%04u, %02u:%02u",
                $p["day"], $p["month"], $p["year"],
                $p["hour"], $p["minute"]);

            return $res;
        }));
    }
}
