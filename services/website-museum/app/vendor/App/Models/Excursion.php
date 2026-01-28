<?php

class App_Models_Excursion extends App_Core_Model
{
    public static $tableName = "excursions";

    public static $fieldNames = array(
        "id",
        "title",
        "contents",
        "short_title",
        "subtitle",
        "published",
        "small_image",
        "price",
        "duration",
        "order",
        "gallery",
        );

    public function save()
    {
        $this->published = (int)(bool)$this->published;
        $this->small_image = App_Core_Common::fixImageLink($this->small_image);

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
        $res["contents_html"] = App_Core_Markdown::format($res["contents"]);
        return $res;
    }
}
