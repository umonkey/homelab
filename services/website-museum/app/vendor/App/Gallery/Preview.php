<?php

class App_Gallery_Preview extends App_Core_View
{
    public function onGet()
    {
        if (!($ids = @$_GET["files"]))
            $ids = array();
        else
            $ids = preg_split('@,@', $ids, 0, PREG_SPLIT_NO_EMPTY);

        $files = array();
        foreach ($ids as $id) {
            if ($file = App_Models_File::getById($id, true))
                $files[] = $file->forTemplate();
        }

        $t = new App_Core_TwigTemplate(get_app_path("templates/gallery-preview.twig"));
        $html = $t->render(array(
            "files" => $files,
            ));

        $html = trim($html);
        $html = App_Common::stripSpaces($html);

        return $this->sendJSON(array(
            "html" => $html,
            ));
    }
}
