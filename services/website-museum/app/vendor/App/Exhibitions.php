<?php

class App_Exhibitions extends App_Core_View
{
    public function onGet()
    {
        $open = App_Models_Exhibition::whereTemplate("`published` = 1 AND `closed` = 0 ORDER BY `date` DESC, `id` DESC");
        $closed = App_Models_Exhibition::whereTemplate("`published` = 1 AND `closed` = 1 ORDER BY `date` DESC, `id` DESC");

        return $this->sendPage("exhibitions.twig", array(
            "tab" => "exhibitions",
            "closed" => $closed,
            "open" => $open,
            ));
    }
}
