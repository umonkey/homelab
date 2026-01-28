<?php

class App_Models_Place extends App_Core_Model
{
    public static $tableName = "places";

    public static $fieldNames = array(
        "id",
        "title",
        "contents",
        "subtitle",
        "published",
        "lat",
        "lng",
        "gallery",
        );

    public function save()
    {
        $this->published = (int)(bool)$this->published;
        $this->html = $this->renderMapHTML();
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

    public function getMapHTML()
    {
        if ($this["html"])
            return $this["html"];

        return $this->renderMapHTML();
    }

    protected function renderMapHTML()
    {
        $link = $this->id ? "/places/{$this->id}" : "/places";

        $html = "<p><strong><a href='{$link}'>{$this->title}</a></strong></p>";

        if ($this["contents"]) {
            $contents = App_Core_Markdown::format($this->contents);
            if (preg_match('@(<p>(.*)</p>)@', $contents, $m)) {
                $html .= $m[1];

                if (preg_match('@<img src=\'([^\']+)\'@', $contents, $m)) {
                    $html .= "<p><a href='{$link}'><img src='{$m[1]}' alt='иллюстрация'/></a></p>";
                }

                $html .= "<p><a href='{$link}'>Подробнее</a></p>";
            }
        }

        if ($gallery = App_Common::prepareGallery($this["gallery"])) {
            $html .= "<img src='{$gallery[0]["small"]}' />";
        }

        return $html;
    }
}
