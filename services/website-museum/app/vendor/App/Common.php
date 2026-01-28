<?php

class App_Common
{
    public static function breadcrumbs(array $items)
    {
        $path = array();

        $path[] = array(
            "link" => "/",
            "label" => "Главная",
            );

        foreach ($items as $k => $v)
            $path[] = array(
                "link" => $k,
                "label" => $v,
                );

        return $path;
    }

    public static function humanDate($date)
    {
        $months = array("января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");

        if (!preg_match('@^(\d{4})-(\d{2})-(\d{2})@', $date, $m))
            return $date;

        $day = $m[3];
        $month = $m[2];
        $year = $m[1];

        if ($year == strftime("%Y"))
            return sprintf("%u %s", $day, $months[$month - 1]);
        else
            return sprintf("%u %s, %u", $day, $months[$month - 1], $year);
    }

    public static function stripSpaces($html)
    {
        return preg_replace('@>\s+<@', '><', $html);
    }

    public static function parseText($text)
    {
        $files = array();

        /*
        $text = preg_replace_callback('^/files/(\d+)/.+^', function ($m) use (&$files) {
            if ($f = App_Models_File::getById($m[1], true)) {
                $src = $f->getSource();

                $files[] = array(
                    "id" => intval($m[1]),
                    "small" => "/files/{$m[1]}/small.jpg",
                    "large" => "/{$src}",
                    );
            }

            return "";
        }, $text);

        $text = preg_replace_callback('^(https://files.umonkey.net/.+)/(sm|md|lg)\.jpg^', function ($m) use (&$files) {
            $files[] = array(
                "small" => "{$m[1]}/md.jpg",
                "large" => "{$m[1]}/lg.jpg",
                );

            return "";
        }, $text);

        $text = preg_replace_callback('^(https?://.+\.jpg)^', function ($m) use (&$files) {
            $folder = get_doc_path("files/remote");
            if (!is_dir($folder))
                @mkdir($folder, 0755, true);

            $hash = substr(md5($m[1]), 0, 10);

            $meta = get_doc_path("files/remote/{$hash}.txt");
            if (!is_readable($meta))
                file_put_contents($meta, $m[1]);

            $files[] = array(
                "small" => "/files/remote/{$hash}.jpg",
                "large" => $m[1],
                );

            return "";
        }, $text);
        */

        $html = App_Core_Markdown::format($text);

        return array($html, $files);
    }

    /**
     * Подготовка к выводу галереи.
     *
     * Разворачивает список идентификаторов в массив описаний файлов.
     *
     * @param string $ids Идентфикаторы файлов, через запятую.
     * @return array Описание файлов.
     **/
    public static function prepareGallery($ids)
    {
        $images = array();

        $ids = preg_split('@,@', $ids, 0, PREG_SPLIT_NO_EMPTY);
        foreach ($ids as $id) {
            $file = App_Models_File::getById($id, true);
            if ($file and $file->getType() == "image") {
                $tmp = $file->forTemplate();
                $images[] = array(
                    "id" => $file["id"],
                    "small" => $tmp["images"]["small"]["url"],
                    "large" => $tmp["images"]["original"]["url"],
                    );
            }
        }

        return $images;
    }
}
