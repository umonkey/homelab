<?php

class App_Admin_Docs extends App_Admin_Handler
{
    public function onGet()
    {
        $typeName = func_get_arg(0);

        $config = App_Admin_Common::getConfig();
        if (!array_key_exists($typeName, $config["types"]))
            $this->notfound();

        $type = $config["types"][$typeName];
        $type["type"] = $typeName;

        $docs = $this->getDocs($type);

        return $this->sendPage($type["admin_list_template"], array(
            "type" => $type,
            "docs" => $docs,
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/admin" => "Управление",
                "/admin/{$typeName}" => $type["title"],
                )),
            ));
    }

    public function getDocs(array $type)
    {
        $res = array();

        $where = $type["admin_list_where"];

        foreach ($type["model"]::where($where) as $doc) {
            $tmp = $doc->forTemplate();
            if (!empty($type["view_link"]))
                $tmp["view_link"] = App_Admin_Common::formatLink($type["view_link"], $tmp);
            $res[] = $tmp;
        }
        return $res;
    }
}
