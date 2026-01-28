<?php

class App_Photo_Show extends App_Core_View
{
    public function onGet()
    {
        $id = func_get_arg(0);

        $photo = App_Models_Photo::getById($id)->forTemplate();
        if (!$photo["published"])
            return $this->sendPage("photo-forbidden.twig", array(
                ), "403 Forbidden");

        $editLink = "/admin/photo/{$id}/edit?back=/photo/{$id}";
        $this->jsdata["edit_link"] = $editLink;

        $data = array(
            "tab" => "photo",
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/photo" => "Архив фотографий",
                "/photo/{$id}" => $photo["title"],
                )),
            "photo" => $photo,
            "prev_photo" => $this->getPrev($photo["id"]),
            "next_photo" => $this->getNext($photo["id"]),
            "edit_link" => $editLink,
            );

        $this->addSchemaOrg($photo);

        return $this->sendPage("photo.twig", $data);
    }

    protected function getPrev($id)
    {
        $rows = App_Models_Photo::where("published = 1 AND id < ? ORDER BY id DESC", array($id));
        foreach ($rows as $row)
            return "/photo/{$row->id}";

        $rows = App_Models_Photo::where("published = 1 ORDER BY id DESC");
        foreach ($rows as $row)
            return "/photo/{$row->id}";
    }

    protected function getNext($id)
    {
        $rows = App_Models_Photo::where("published = 1 AND id > ? ORDER BY id", array($id));
        foreach ($rows as $row)
            return "/photo/{$row->id}";

        $rows = App_Models_Photo::where("published = 1 ORDER BY id");
        foreach ($rows as $row)
            return "/photo/{$row->id}";
    }

    protected function addSchemaOrg($photo)
    {
        $data = array();

        // ImageObject
        $s = array(
            "@context" => "http://schema.org",
            "@type" => "ImageObject",
            "contentUrl" => "https://seb-museum.ru{$photo["images"]["old"]["o"]["link"]}",
            "name" => $photo["title"],
            );
        $data[] = $s;

        $this->addJSONLD("photo_json_ld", $data);
    }
}
