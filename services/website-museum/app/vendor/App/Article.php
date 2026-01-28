<?php

class App_Article extends App_Core_View
{
    public function onGet()
    {
        $id = func_get_arg(0);
        $object = App_Models_Article::getById($id);

        if (!$object->published)
            $this->notfound();

        return $this->sendPage("article.twig", array(
            "tab" => "articles",
            "object" => $object->forTemplate2(),
            ));
    }
}
