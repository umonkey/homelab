<?php
/**
 * Обобщённый редактор моделей.
 *
 * В наследнике нужно указать класс модели и название шаблона.
 **/

class App_Core_Editor extends App_Core_View
{
    protected $modelName;

    protected $templateName;

    protected $tab = null;

    protected $boolFields = array();

    public function handle()
    {
        $args = func_get_args();
        if ($args)
            $object = $this->getById($args[0]);
        else
            $object = $this->getNew();

        switch ($this->request->getMethod()) {
        case "GET":
            return $this->modelGet($object);
        case "POST":
            return $this->modelPost($object);
        default:
            throw new Framework_Errors_ServiceUnavailable("method not supported");
        }
    }

    protected function modelGet(App_Core_Model $object)
    {
        if (!$this->checkAccess($object))
            throw new Framework_Errors_Forbidden;

        $data = array(
            "object" => $object->forTemplate(),
            "tab" => $this->tab,
            );

        $this->extendForm($object, $data);

        return $this->sendPage($this->templateName, $data);
    }

    protected function modelPost(App_Core_Model $object)
    {
        try {
            $form = $this->getForm();

            if (!$this->checkAccess($object))
                throw new Framework_Errors_Forbidden;

            $object->update($form);
            $object->save();

            return $this->getEditResponse($object);
        } catch (Exception $e) {
            log_exception($e);

            return $this->sendJSON(array(
                "message" => sprintf("%s: %s", get_class($e), $e->getMessage()),
                ));
        }
    }

    /**
     * Загрузка объекта по идентификатору.
     *
     * Использует класс, указанный в $this->modelName.
     *
     * @param int $id Идентификатор объекта.
     * @return App_Core_Model Загруженный объект.
     **/
    protected function getById($id)
    {
        if (!($class = $this->modelName))
            throw new RuntimeException("modelName not set");

        if (!class_exists($class))
            throw new RuntimeException("model {$class} does not exist");

        $func = array($class, "getById");
        $args = array($id);

        return call_user_func_array($func, $args);
    }

    protected function getNew()
    {
        if (!($class = $this->modelName))
            throw new RuntimeException("modelName not set");

        if (!class_exists($class))
            throw new RuntimeException("model {$class} does not exist");

        return new $class;
    }

    /**
     * Получение данных формы.
     *
     * @return array Содержимое формы (POST).
     **/
    protected function getForm()
    {
        $form = $this->request->getForm();

        foreach ($this->boolFields as $field) {
            $val = (int)($this->request->arg($field) == "yes");
            $form[$field] = $val;
        }

        return $form;
    }

    /**
     * Дополнение формы.
     *
     * Используй для вывода дополнительных данных: значений выпадающих списков, итп.
     *
     * @param array &$form Данные для использования в шаблоне, object = редактируемый объект.
     * @return void
     **/
    protected function extendForm(App_Core_Model $object, array &$form)
    {
    }

    /**
     * Обработка принятых файлов.
     *
     * @param App_Core_Model $object Редактируемый объект.
     * @param array $files Массив со всеми принятыми файлами.
     * @return void
     **/
    protected function handleFiles(App_Core_Model $object)
    {
        $files = $this->request->files();
        if (empty($files["files"]))
            return;

        foreach ($files["files"] as $file) {
            if ($file["error"] == 0) {
                $f = $object->addFile($file);
                $f->save();
            }
        }
    }

    protected function getEditResponse($object)
    {
        $link = $object->getViewLink();
        if (!$link)
            $link = $object->getListLink();

        if ($link)
            return $this->sendJSON(array(
                "redirect" => $link,
                ));
        else
            return $this->sendJSON(array(
                "message" => "Изменения сохранены.",
                ));
    }

    protected function checkAccess(App_Core_Model $object)
    {
        return true;
    }
}
