<?php

class App_Models_Exhibition extends App_Core_Model
{
    public static $tableName = "exhibitions";

    public static $fieldNames = array(
        "id",
        "title",
        "date",
        "contents",
        "short_title",
        "subtitle",
        "published",
        "closed",
        "small_image",
        "gallery",
        );

    public function save()
    {
        $this["published"] = (int)(bool)$this["published"];
        $this["closed"] = (int)(bool)$this["closed"];
        $this["small_image"] = App_Core_Common::fixImageLink($this["small_image"]);

        return parent::save();
    }

    public function forTemplate()
    {
        $res = parent::forTemplate();
        $res["published"] = (bool)(int)$this->published;
        $res["closed"] = (bool)(int)$this->closed;

        if (preg_match('@^(\d\d\d\d)-(\d\d)-(\d\d)$@', $res["date"], $m))
            $res["date_human"] = $m[3] . "." . $m[2] . "." . $m[1];

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
