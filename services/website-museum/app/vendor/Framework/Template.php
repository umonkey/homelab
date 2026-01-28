<?php

class Framework_Template
{
	protected $templateName;

	protected $folder;

	public function __construct($templateName, $folder = null)
	{
		$this->templateName = $templateName;
		$this->folder = $folder;
	}

	public function render(array $data = array())
	{
        $defaults = Framework_Config::get("template_defaults", array());
        $data = array_merge($defaults, $data);

        $data["now"] = strftime("%Y-%m-%d %H:%M:%S");

        $data["css_link"] = $this->getStaticLink("static/styles.min.css");
        $data["js_link"] = $this->getStaticLink("static/scripts.js");
        $data["host"] = $_SERVER["HTTP_HOST"];
        $data["path"] = $_SERVER["REQUEST_URI"];

        if (@$_GET["debug"] == "tpl")
            debug($data);

        // Rendering logic follows.

        if ($this->folder) {
            $root = APP_ROOT . "/templates/{$this->folder}";
            $cache = DOC_ROOT . "/data/tmp/twig_{$this->folder}_cache";
        } else {
            $root = APP_ROOT . "/templates";
            $cache = DOC_ROOT . "/data/tmp/twig_cache";
        }

        $twig_loader = new Twig_Loader_Filesystem($root);

        $twig = new Twig_Environment($twig_loader, array(
            "cache" => $cache,
            "auto_reload" => true,
            ));

        // http://twig.sensiolabs.org/doc/advanced.html#filters

        $twig->addFilter(new Twig_SimpleFilter("phone", function ($string) {
            $phone = preg_replace('@[^0-9]+@', '', $string);

            if (preg_match('@7(\d\d\d)(\d\d\d)(\d\d)(\d\d)@', $phone, $m))
                return "+7 ({$m[1]}) {$m[2]}-{$m[3]}-{$m[4]}";

            return $string;
        }));

        $twig->addFilter(new Twig_SimpleFilter("cost", function ($cost) {
            if (!floatval($cost))
                return "";
            return sprintf("%.2f", $cost);
        }));

        $twig->addFilter(new Twig_SimpleFilter("datetime", function ($string) {
            if (empty($string))
                return "";

            $p = date_parse_from_format("Y-m-d H:i:s", $string);
            $res = sprintf("%02u.%02u.%04u, %02u:%02u",
                $p["day"], $p["month"], $p["year"],
                $p["hour"], $p["minute"]);

            return $res;
        }));

		/*
        $twig->addFilter(new Twig_SimpleFilter("short_date", function ($string) {
            $p = TreeDB_Util::parseDate($string);
            if ($p === null)
                return "";

            $res = sprintf("%02u.%02u.%04u", $p["day"], $p["month"], $p["year"]);
            return $res;
        }));
		*/

        $twig->addFilter(new Twig_SimpleFilter("price", function ($string) {
            if (empty($string))
                return "";

            $n = number_format($string, 0);
            return $n . " р.";
        }));

        $twig->addFilter(new Twig_SimpleFilter("markdown", function ($string) {
			return Framework_Markdown::format($string);
        }));

        $html = $twig->render($this->templateName, $data);

		// Strip some white space.
        $html = preg_replace('@>\s+<@', '><', $html);

        return $html;
	}

    protected function getStaticLink($path)
    {
        $hash = md5(file_get_contents(APP_ROOT . "/" . $path));
        return "/{$path}?hash={$hash}";
    }
}
