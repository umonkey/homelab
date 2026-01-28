<?php

class App_Files_Thumbnail extends App_Core_View
{
    public function onGet()
    {
        $id = func_get_arg(0);
        $slug = func_get_arg(1);

        $f = App_Models_File::getById($id, true);
        if (!$f)
            return $this->notfound("файл не найден");

        $src = get_doc_path($f->getSource());
        if (!is_readable($src))
            return $this->notfound("source image {$src} not found");

        $dst = get_doc_path("files/{$id}/{$slug}");

        $img = App_Core_Image::fromFile($src);
        $img = $img->resizeMin(200);
        $img->sharpen();

        $data = $img->getJPEG(80);
        file_put_contents($dst, $data);

        return new Framework_Response($data, "200 OK", array(
            "Content-Type" => "image/jpeg",
            ));
    }
}
