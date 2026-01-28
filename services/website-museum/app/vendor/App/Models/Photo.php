<?php

class App_Models_Photo extends App_Core_Model
{
    public static $tableName = "photos";

    public static $fieldNames = array(
        "id",
        "created",
        "published",
        "title",
        "description",
        "lat",
        "lng",
        "vk_id",
        );

    public static function findPublished()
    {
        return self::where("published = 1 ORDER BY id DESC");
    }

    public static function findPublished2()
    {
        return self::whereTemplate("published = 1 ORDER BY id DESC");
    }

    public function findImages()
    {
        $images = array();

        $types = array("old", "new");
        $sizes = array("o", "p", "l", "s");

        foreach ($types as $type) {
            foreach ($sizes as $size) {
                $src = get_doc_path($path = "files/photos/{$this["id"]}/{$type}_{$size}.jpg");
                if (is_readable($src)) {
                    if ($s = @getimagesize($src)) {
                        $images[$type][$size] = array(
                            "link" => "/" . $path . "?ts=" . filemtime($src),
                            "path" => $src,
                            "width" => $s[0],
                            "height" => $s[1],
                            );
                    }
                }
            }
        }

        return $images;
    }

    public function forTemplate()
    {
        $res = parent::forTemplate();
        $res["description_html"] = App_Core_Markdown::format($res["description"]);
        $res["images"] = $this->findImages();

        if (empty($res["title"]))
            $res["title"] = "Без названия";

        return $res;
    }

    public function save()
    {
        $this->data = array_merge(array(
            "created" => strftime("%Y-%m-%d %H:%M:%S"),
            ),  $this->data);

        $this->published = (int)(bool)$this->published;

        return parent::save();
    }

    public function updateFiles(array $files)
    {
        $types = array("old", "new");

        $folder = get_doc_path("files/photos/{$this["id"]}");
        if (!is_dir($folder))
            mkdir($folder, 0755, true);

        foreach ($files as $ft => $items) {
            foreach ($items as $item) {
                if ($item["error"] == 4)
                    continue;

                if (0 === strpos("image/", $item["type"]))
                    ;
                elseif (preg_match('@\.(jpg|jpeg|png|gif)$@', $item["name"]))
                    ;
                else {
                    log_debug("not an image: %s, name: %s", $item["type"], $item["name"]);
                    continue;
                }

                $src = "{$folder}/{$ft}_o.jpg";

                if (!move_uploaded_file($item["tmp_name"], $src))
                    throw new RuntimeException("error saving uploaded file");

                $img = App_Core_Image::fromFile($src);

                $img1 = $img->resizeMin(200, false);
                $img1->sharpen();
                $data = $img1->getJPEG(85);
                write_file("{$folder}/{$ft}_s.jpg", $data);

                $img1 = $img->resizeMax(2000, false);
                $data = $img1->getJPEG(75);
                write_file("{$folder}/{$ft}_l.jpg", $data);
            }
        }

        $this->refreshFiles();
    }

    /**
     * Обновляет уменьшенные изображения.
     *
     * Использует old_p.jpg, если нет -- old_o.jpg.
     **/
    public function refreshFiles()
    {
        $images = $this->findImages();

        foreach ($images as $ft => $image) {
            $src = $image["o"]["path"];
            $folder = dirname($src);
            $img = App_Core_Image::fromFile($src);

            if ($ft == "old" and is_readable($crop = $folder . "/crop.txt")) {
                $crop = file_get_contents($crop);
                list($x, $y, $w, $h) = explode(",", $crop);

                $img = $img->crop($x, $y, $w, $h);

                App_Util::writeFile("{$folder}/old_p.jpg", $img->getJPEG(85));
            }

            $img1 = $img->resizeMin(200, false);
            $img1->sharpen();
            $data = $img1->getJPEG(85);
            App_Util::writeFile("{$folder}/{$ft}_s.jpg", $data);

            $img1 = $img->resizeMax(2000, false);
            $data = $img1->getJPEG(75);
            App_Util::writeFile("{$folder}/{$ft}_l.jpg", $data);
        }
    }
}
