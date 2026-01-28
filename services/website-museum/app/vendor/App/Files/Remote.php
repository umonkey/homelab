<?php

class App_Files_Remote extends App_Core_View
{
    public function onGet()
    {
        $id = func_get_arg(0);

        $meta = get_doc_path("files/remote/{$id}.txt");
        if (!is_readable($meta))
            return $this->notfound();

        $url = file_get_contents($meta);

        $data = @file_get_contents($url);
        if (false === $data)
            return $this->notfound();

        $img = App_Core_Image::fromString($data);
        $img->resizeMin(200);
        $img->sharpen();

        $data = $img->getJPEG(80);
        file_put_contents(get_doc_path("files/remote/{$id}.jpg"), $data);

        return new Framework_Response($data, "200 OK", array(
            "Content-Type" => "image/jpeg",
            ));
    }
}
