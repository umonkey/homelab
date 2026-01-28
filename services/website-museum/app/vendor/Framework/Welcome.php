<?php

class Framework_Welcome extends Framework_View
{
    public function onGet()
    {
        $html = $this->renderPage("welcome.twig");
        return $this->sendHTML($html);
    }

    protected function renderPage($template, array $vars = array())
    {
		$template = get_app_path("templates/{$template}");
        $t = new Framework_TwigTemplate($template);
        $html = $t->render($vars);
        return $html;
    }
}
