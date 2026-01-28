<?php

class App_Events extends App_Core_View
{
    public function onGet()
    {
        $today = strftime('%Y-%m-%d');

        $pending = App_Models_Event::whereTemplate("`published` = 1 AND `date` >= ? ORDER BY `date` DESC, `time`", array($today));
        $past = App_Models_Event::whereTemplate("`published` = 1 AND `date` < ? ORDER BY `date` DESC, `time`", array($today));

        return $this->sendPage("events.twig", array(
            "tab" => "events",
            "pending" => $pending,
            "past" => $past,
            ));
    }
}
