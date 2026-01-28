<?php

class App_Models_Object extends App_Core_Model
{
    public static $tableName = "objects";

    public static $fieldNames = array(
        "id",
        "title",
        "contents",
        "short_title",
        "subtitle",
        "published",
        "small_image",
        "gallery",
        );

    public function save()
    {
        $this["published"] = (int)(bool)$this["published"];
        $this["small_image"] = App_Core_Common::fixImageLink($this["small_image"]);

        return parent::save();
    }

    public function forTemplate()
    {
        $res = parent::forTemplate();
        $res["published"] = (bool)(int)$this->published;

        return $res;
    }

    public function forTemplate2()
    {
        $res = self::forTemplate();

        list($html, $files) = App_Common::parseText($res["contents"]);
        $res["contents_html"] = $html;
        $res["contents_files"] = $files;

        return $res;
    }
}
