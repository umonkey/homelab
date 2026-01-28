<?php

class App_Admin_Create extends App_Admin_Edit
{
    public function onGet()
    {
        $args = func_get_args();
        $type = $args[0];

        $conf = App_Admin_Common::getTypeConfig($type);

        $object = new $conf["model"];

        if (isset($conf["template_create"]))
            $template = $conf["template_create"];
        elseif (isset($conf["template_edit"]))
            $template = $conf["template_edit"];
        else {
            $this->logger->error("editor template not set for type %s", $type);
            $this->unavailable("Cannot create object of this type at this time.");
        }

        return $this->sendPage($template, array(
            "object" => $object->forTemplate(),
            "type" => $conf,
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/admin" => "Управление",
                "/admin/{$type}" => $conf["title"],
                "/admin/{$type}/add" => "Добавление",
                )),
            ));
    }

    public function onPost()
    {
        try {
            $args = func_get_args();
            $type = $args[0];

            $conf = App_Admin_Common::getTypeConfig($type);

            $object = new $conf["model"];
            $object->update($this->getFormFields($conf));
            $object->save();

            if ($files = $this->getFiles()) {
                $object->updateFiles($files);
            }

            /*
            $link = $conf["view_link"];
            $link = str_replace("@ID@", $object->id, $link);
            */

            $this->logAction("Created {type} with id={id}.", [
                "type" => $type,
                "id" => $object->id,
            ]);

            if ($type == "photo")
                $link = "/admin/photo";
            else
                $link = "/admin/{$type}/{$object->id}/edit";

            if ($tmp = @$_POST["back"])
                $link = $_POST["back"];

            return $this->sendJSON(array(
                "redirect" => $link,
                ));
        } catch (Exception $e) {
            return $this->sendJSON(array(
                "message" => $e->getMessage(),
                ));
        }
    }
}
