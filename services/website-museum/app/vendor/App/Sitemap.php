<?php

class App_Sitemap extends App_Core_View
{
    public function onGet()
    {
        $xml = $this->getXML();

        return $this->sendXML($xml);
    }

    protected function getXML()
    {
        $base = "https://seb-museum.ru";

        $urls = array();

        $config = App_Admin_Common::getConfig();
        foreach ($config["types"] as $type => $tconf) {
            if (!empty($tconf["public_list"]))
                $urls[] = $tconf["public_list"];
            if (!empty($tconf["view_link"])) {
                $objects = $tconf["model"]::findPublished();
                foreach ($objects as $object) {
                    $link = App_Admin_Common::formatLink($tconf["view_link"], $object->forTemplate());
                    $urls[] = $link;
                }
            }
        }

        $xml = "<?xml version='1.0' encoding='utf-8'?".">";
        $xml .= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";
        foreach ($urls as $url)
            $xml .= "<url><loc>{$base}{$url}</loc></url>";
        $xml .= "</urlset>\n";
        return $xml;
    }
}
