<?php

class App_Admin_List extends App_Admin_Handler
{
    public function onGet()
    {
        $config = App_Admin_Common::getConfig();

        $docs = $this->getDocs($config);

        return $this->sendPage("admin-list.twig", array(
            "config" => $config,
            "documents" => $docs,
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/admin" => "Управление",
                )),
            ));
    }

    /**
     * Loads most recent documents of configured types.
     **/
    protected function getDocs(array $config)
    {
        $res = array();

        if (empty($config["types"]))
            return array();

        $recent = isset($config["recent"]) ? $config["recent"] : 10;

        foreach ($config["types"] as $name => $type) {
            if (!$type["dashboard"])
                continue;

            $type = array_merge(array(
                "model" => null,
                "recent" => 10,
                "title" => "Documents of type {$name}",
                "public_list" => null,
                ), $type);

            $error = null;

            $model = $type["model"];

            if (!method_exists($model, "findRecent")) {
                log_warning("admin-list: method %s::findRecent does not exist -- showing empty doc list", $model);
                $docs = array();
            } else {
                $docs = array();
                try {
                    foreach ($model::findRecent($recent) as $doc)
                        $docs[] = $doc->forTemplate();
                } catch (Exception $e) {
                    log_exception($e);
                    $error = "Список недоступен: " . $e->getMessage();
                }
            }

            $res[] = array(
                "type" => $name,
                "title" => $type["title"],
                "public_list" => $type["public_list"],
                "error" => $error,
                "docs" => $docs,
                );
        }

        return $res;
    }
}
