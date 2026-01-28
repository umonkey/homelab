<?php

class App_Models_Event extends App_Core_Model
{
    public static $tableName = "events";

    public static $fieldNames = array(
        "id",
        "date",
        "time",
        "title",
        "description",
        "published",
        "gallery",
        );

    public function save()
    {
        $this->published = (int)(bool)$this->published;

        return parent::save();
    }

    public static function findPublished()
    {
        return self::where("`published` = 1 ORDER BY `date` DESC, `time`");
    }

    public function forTemplate()
    {
        $res = parent::forTemplate();

        $res["date_human"] = App_Common::humanDate($res["date"]);
        $res["time_human"] = substr($res["time"], 0, 5);

        return $res;
    }

    public function forTemplate2()
    {
        $res = self::forTemplate();

        list($html, $files) = App_Common::parseText($res["description"]);
        $res["contents_html"] = $html;
        $res["contents_files"] = $files;

        return $res;
    }
}
