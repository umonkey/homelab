<?php

class App_Event extends App_Core_View
{
    public function onGet()
    {
        $id = func_get_arg(0);
        $object = App_Models_Event::getById($id);

        if (!$object->published)
            $this->notfound();

        $back = $this->request->getPath();

        $editLink = "/admin/event/{$id}/edit?back=" . urlencode($back);
        $this->jsdata["edit_link"] = $editLink;

        return $this->sendPage("event.twig", array(
            "tab" => "events",
            "object" => $object->forTemplate2(),
            "edit_link" => $editLink,
            ));
    }
}
