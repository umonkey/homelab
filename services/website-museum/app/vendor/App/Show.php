<?php

class App_Show extends App_Core_View
{
    public function onGet()
    {
        $args = func_get_args();
        $config = App_Admin_Common::getTypeConfig($args[0]);

        if (count($args) == 2)
            return $this->onGetItem($config, $args[1], $args[0]);
        else
            return $this->onGetList($config, $args[0]);
    }

    protected function onGetItem(array $config, $itemId, $type)
    {
        $model = $config["model"];

        $object = $model::getById($itemId);  // throws NotFound

        if (empty($config["template_item"])) {
            log_error("template_item not set for %s", $model);
            $this->notfound();
        }

        $back = $this->request->getPath();

        $path = App_Common::breadcrumbs(array(
            $config["public_list"] => $config["title"],
            $this->request->getPath() => $object->title,
            ));

        $data = array(
            "tab" => @$config["tab"],
            "type" => $type,
            "object" => $object->forTemplate2(),
            "breadcrumbs" => $path,
            "edit_link" => "/admin/{$type}/{$itemId}/edit?back=" . urlencode($back),
            );

        $this->jsdata["edit_link"] = $data["edit_link"];

        return $this->sendPage($config["template_item"], $data);
    }

    public function onGetList(array $config, $type)
    {
        $model = $config["model"];

        if (empty($config["list_where"])) {
            log_error("no list_where for %s", $model);
            $this->notfound();
        }

        if (empty($config["template_list"])) {
            log_error("no template_list for %s", $model);
            $this->notfound();
        }

        return $this->sendPage($config["template_list"], array(
            "tab" => @$config["tab"],
            "type" => $type,
            "objects" => $model::whereTemplate($config["list_where"]),
            "breadcrumbs" => App_Common::breadcrumbs(array(
                $config["public_list"] => $config["title"],
                )),
            ));
    }
}
