<?php

class App_Admin_Edit extends App_Admin_Handler
{
    protected $logger;

    public function __construct(Framework_Request $request)
    {
        parent::__construct($request);

        $this->logger = new Framework_Logger("editor: ");
    }

    public function onGet()
    {
        $args = func_get_args();
        $type = $args[0];
        $id = $args[1];

        $conf = App_Admin_Common::getTypeConfig($type);

        $conf["type"] = $type;
        $model = $conf["model"];

        $object = $model::getById($id, true);
        if (!$object) {
            $this->logger->debug("model %s can't find object with id %u", $model, $id);
            $this->notfound($conf["notfound"]);
        }

        $template = $conf["template_edit"];

        $path = array(
            "/admin" => "Управление",
            "/admin/{$type}" => $conf["title"],
            );

        if ($id)
            $path["/admin/{$type}/{$id}/edit"] = $object->title;
        else
            $path["/admin/{$type}/add"] = "Добавление";

        $data = array(
            "type" => $conf,
            "object" => $object->forTemplate(),
            "breadcrumbs" => App_Common::breadcrumbs($path),
            );

        return $this->sendPage($template, $data);
    }

    public function onPost()
    {
        $args = func_get_args();
        $type = $args[0];
        $id = $args[1];

        $conf = App_Admin_Common::getTypeConfig($type);

        $model = $conf["model"];

        $object = $model::getById($id, true);
        if (!$object) {
            $this->logger->debug("model %s can't find object with id %u", $model, $id);
            $this->notfound($conf["notfound"]);
        }

        $form = $this->getFormFields($conf);
        $object->update($form);

        if ($files = $this->getFiles())
            $object->updateFiles($files);

        $object->save();

        $this->logAction("Edited {type} with id={id}.", [
            "type" => $type,
            "id" => $id,
        ]);

        if ($back = $this->request->arg("back"))
            return $this->sendJSON(array(
                "redirect" => $back,
                ));

        return $this->sendJSON(array(
            "message" => "Изменения сохранены.",
            ));
    }

    protected function getFormFields(array $conf)
    {
        $form = $this->request->getForm();

        foreach ($conf["boolean"] as $field) {
            if (!array_key_exists($field, $form))
                $form[$field] = false;
            else
                $form[$field] = $form[$field] == "yes";
        }

        $files = $this->request->files();
        foreach ($files as $k => $v)
            $form[$k] = $v;

        return $form;
    }
}
