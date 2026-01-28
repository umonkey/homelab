<?php

class App_Home extends App_Core_View
{
    public function onGet()
    {
        $exhibitions = App_Models_Exhibition::whereTemplate("`published` = 1 AND `closed` = 0 ORDER BY `date` DESC LIMIT 3");
        $objects = App_Models_Object::whereTemplate("`published` = 1 ORDER BY `id` DESC LIMIT 3");

        $today = strftime("%Y-%m-%d");
        $pending = App_Models_Event::whereTemplate("`published` = 1 AND `date` >= ? ORDER BY `date`, `time` LIMIT 3", array($today));
        $past = App_Models_Event::whereTemplate("`published` = 1 AND `date` < ? ORDER BY `date` DESC, `time` DESC LIMIT 3", array($today));

        $splash = App_Models_Splash::whereTemplate("1 ORDER BY id DESC");

        return $this->sendPage("index.twig", array(
            "page_title" => "Себежский краеведческий музей",
            "splash" => $splash,
            "exhibitions" => $exhibitions,
            "objects" => $objects,
            "pending_events" => $pending,
            "past_events" => $past,
            "today" => strftime("%m%d"),
            ));
    }
}
