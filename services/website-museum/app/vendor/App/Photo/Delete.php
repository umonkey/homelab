<?php

class App_Photo_Delete extends App_Admin_Handler
{
    public function onPost()
    {
        $id = func_get_arg(0);
        $photo = App_Models_Photo::getById($id);

        if ((int)$photo["published"]) {
            $photo["published"] = 0;
            $photo->save();
            $status = "draft";
        } else {
            $photo->delete();
            $status = "deleted";
        }

        return $this->sendJSON(array(
            "status" => $status,
            ));
    }
}
