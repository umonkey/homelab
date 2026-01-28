<?php

class App_Photo_Share extends App_Admin_Handler
{
    protected $vk;

    public function prepare()
    {
        parent::prepare();

        $this->requireAdmin();

        $s = $this->sessionRead();
        if (empty($s["vk"]["token"]))
            throw new Framework_Errors_Forbidden;

        $this->vk = new Framework_VK($s["vk"]["token"]);
    }

    public function onGet()
    {
        $id = func_get_arg(0);

        $photo = App_Models_Photo::getById($id);
        if (!$photo["published"])
            return $this->forbidden();

        $albums = array();
        foreach ($this->vk->call("photos.getAlbums") as $a)
            $albums[] = array(
                "id" => $a["aid"],
                "label" => $a["title"],
                );

        return $this->sendPage("photo-share.twig", array(
            "photo" => $photo->forTemplate(),
            "albums" => $albums,
            ));
    }

    public function onPost()
    {
        $id = func_get_arg(0);

        $photo = App_Models_Photo::getById($id);
        if (!$photo["published"])
            return $this->forbidden();

        $form = $this->getForm(array(
            "album" => null,
            ));

        $images = $photo->findImages();
        $src = isset($images["old"]["p"]) ? $images["old"]["p"]["path"] : $images["old"]["o"]["path"];
        if (!is_readable($src))
            return $this->notfound("файл с фотографией не найден");

        $caption = $photo["title"];
        $caption .= "\n\n" . $photo["description"];
        $caption .= "\n\nБольше фотографий с описаниями вы можете найти на нашем сайте: https://seb-museum.ru/photo";

        $res = $this->vk->uploadPhoto($src, array(
            "album_id" => $form["album"],
            "caption" => trim($caption),
            "latitude" => $photo["lat"],
            "longitude" => $photo["lng"],
            ));

        if ($res) {
            $photo["vk_id"] = $res[0]["id"];
            $photo->save();
        } else {
            throw new RuntimeException("Не удалось загрузить фотографию.");
        }

        return $this->sendJSON(array(
            "call" => "vk_photo_ready",
            "call_args" => array(
                "link" => "https://vk.com/{$photo["vk_id"]}",
                ),
            ));
    }
}
