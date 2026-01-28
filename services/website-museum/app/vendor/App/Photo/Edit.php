<?php
/**
 * Редактирование изображения.
 *
 * Позволяет кадрировать исходный файл.
 **/

class App_Photo_Edit extends App_Admin_Handler
{
    public function onGet()
    {
        $args = func_get_args();

        $photo = App_Models_Photo::getById($args[0]);

        $data = array();
        $data["tab"] = "photo";
        $data["image"] = $this->getImageInfo($photo);

        $data["breadcrumbs"] = App_Common::breadcrumbs(array(
            "/photo" => "Архив фотографий",
            "/photo/{$photo["id"]}" => $photo["title"],
            "/photo/{$photo["id"]}/old/edit" => "Кадрирование",
            ));

        return $this->sendPage("photo-editor.twig", $data);
    }

    public function onPost()
    {
        $args = func_get_args();
        $photo = App_Models_Photo::getById($args[0]);

        $images = $photo->findImages();
        $image = $images["old"]["o"];

        $form = $this->getForm(array(
            "crop_data" => null,
            ));

        if (empty($form["crop_data"])) {
            $this->revertImage($image);
        } else {
            $this->cropImage($image, $form["crop_data"]);
        }

        $photo->refreshFiles();

        $back = isset($_POST["back"])
            ? $_POST["back"]
            : "/admin/photo/{$photo["id"]}/edit";

        return $this->sendJSON(array(
            "redirect" => $back,
            ));
    }

    protected function cropImage(array $image, $crop)
    {
        list($x, $y, $w, $h) = explode(",", $crop);

        $folder = dirname($image["path"]);

        $img = App_Core_Image::fromFile($image["path"]);
        $img = $img->crop($x, $y, $w, $h);

        $dst = $folder . "/old_p.jpg";
        $jpeg = $img->getJPEG(85);
        App_Util::writeFile($dst, $jpeg);

        App_Util::writeFile($folder . "/crop.txt", $crop);
    }

    protected function revertImage(array $image)
    {
        $folder = dirname($image["path"]);

        $names = array("crop.txt", "old_p.jpg");
        foreach ($names as $name) {
            if (is_readable($path = $folder . "/" . $name))
                @unlink($path);
        }
    }

    protected function getImageInfo($photo)
    {
        $images = $photo->findImages();

        $res = $images["old"]["o"];

        $folder = dirname($res["path"]);
        if (is_readable($path = $folder . "/crop.txt")) {
            $data = file_get_contents($path);
            $res["crop"] = $data;
        } else {
            $res["crop"] = null;
        }

        return $res;
    }
}
