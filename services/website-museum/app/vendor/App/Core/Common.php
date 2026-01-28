<?php

class App_Core_Common
{
    public static function parseURL($url)
    {
        $res = array();

        $parts = explode("?", $url, 2);
        $res["base"] = $parts[0];

        if (count($parts) == 2) {
            $args = explode("&", $parts[1]);
            foreach ($args as $arg) {
                $kv = explode("=", $arg, 2);
                if (count($kv) == 2)
                    $res["args"][$kv[0]] = $kv[1];
                else
                    $res["args"][$kv[0]] = true;
            }
        }

        return $res;
    }

    public static function fixImageLink($link)
    {
        if (empty($link))
            return null;

        if (!($id = self::parseFileId($link)))
            return $link;

        $file = App_Models_File::getById($id, true);
        if (!$file) {
            log_warning("no file with id %u", $id);
            throw new RuntimeException("файл не найден");
        }

        return "/" . $file->getSource();
    }

    public static function parseFileId($link)
    {
        if (preg_match('@/admin/file/(\d+)/edit@', $link, $m))
            return $m[1];
        elseif (preg_match('@^/files/(\d+)@', $link, $m))
            return $m[1];
    }

    public static function getListImageLink($link)
    {
        if (!$link)
            return null;

        if ($id = App_Core_Common::parseFileId($link)) {
            if ($file = App_Models_File::getById($id, true)) {
                $fn = get_doc_path($path = "files/{$id}/md.jpg");
                if (is_readable($fn))
                    return "/" . $path;
            }
        }

        return $link;
    }
}
