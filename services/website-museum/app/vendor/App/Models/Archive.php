<?php

class App_Models_Archive extends App_Core_Model
{
    public static $tableName = "archive";

    public static $fieldNames = array(
        "id",
        "date",
        "title",
        "contents",
        "published",
        "gallery",
        );

    protected function getDefaults()
    {
        return array(
            "date" => strftime("%Y-%m-%d %H:%M:%S"),
            "published" => true,
            );
    }

    public function save()
    {
        $this->published = (int)(bool)$this->published;
        return parent::save();
    }

    public function forTemplate()
    {
        $res = parent::forTemplate();
        $res["published"] = (bool)(int)$this->published;
        $res["date_human"] = App_Common::humanDate($res["date"]);
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
