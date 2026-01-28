<?php

class App_Models_Splash extends App_Core_Model
{
    public static $tableName = "splash";

    public static $fieldNames = array(
        "id",
        "title",
        "description",
        "link",
        "image",
        "gallery",
        );

    public function forTemplate()
    {
        $res = parent::forTemplate();
        $res["published"] = true;
        return $res;
    }

    public function forTemplate2()
    {
        $res = self::forTemplate();
        $res["description_html"] = App_Core_Markdown::format($res["description"]);
        return $res;
    }
}
