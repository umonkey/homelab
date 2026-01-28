<?php

class App_Admin_EditRedir extends App_Core_View
{
    public function onGet()
    {
        $type = func_get_arg(0);
        $id = func_get_arg(1);

        $next = "/admin/{$type}/{$id}/edit";

        return $this->redirect($next);
    }
}
