<?php

class App_Models_Article extends App_Core_Model
{
    public static $tableName = "articles";

    public static $fieldNames = array(
        "id",
        "title",
        "contents",
        "subtitle",
        "published",
        "gallery",
        );

    public function save()
    {
        $this->published = (int)(bool)$this->published;

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
