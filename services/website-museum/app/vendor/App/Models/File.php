<?php

class App_Models_File extends App_Core_Model
{
    public static $tableName = "files";

    public static $fieldNames = array(
        "id",
        "name",
        "description",
        "length",
        "mime_type",
        "date",
        "hash",
        );

    public static function getByHash($hash)
    {
        $files = self::where("`hash` = ? ORDER BY `id`", array($hash));
        foreach ($files as $file)
            return $file;
    }

    public function getSource()
    {
        $sources = glob(get_doc_path("files/{$this["id"]}/original.*"));
        foreach ($sources as $src) {
            $name = basename($src);
            return "files/{$this["id"]}/{$name}";
        }
    }

    public function getType()
    {
        $type = $this["mime_type"];
        $parts = explode("/", $type);
        return $parts[0];
    }

    public function forTemplate()
    {
        $res = parent::forTemplate();
        $res["type"] = $this->getType();

        $pattern = get_doc_path("files/{$this->id}/*.*");
        foreach (glob($pattern) as $fn) {
            $pi = pathinfo($fn);
            $code = $pi["filename"];

            $info = array(
                "size" => filesize($fn),
                "url" => "/files/{$this->id}/" . basename($fn),
                );

            if (preg_match('@\.(jpg|jpeg|png|gif)$@i', $fn)) {
                if ($size = @getimagesize($fn)) {
                    $info["width"] = $size[0];
                    $info["height"] = $size[1];
                }

                $res["images"][$code] = $info;

                if ($code == "original" and empty($res["imaegs"]["small"])) {
                    $res["images"]["small"] = array(
                        "size" => null,
                        "url" => "/files/{$this["id"]}/small.{$pi["extension"]}",
                        "width" => null,
                        "height" => null,
                        );
                }
            } else {
                $res["other"][$code] = $info;
            }
        }

        return $res;
    }

    public function __get($key)
    {
        if ($key == "title")
            return $this->name;
        return parent::__get($key);
    }
}
