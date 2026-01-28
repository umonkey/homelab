<?php

class App_Photo_List extends App_Core_View
{
    public function onGet()
    {
        $photos = App_Models_Photo::findPublished2();

        return $this->sendPage("photos.twig", array(
            "tab" => "photo",
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/photo" => "Архив фотографий",
                )),
            "photos" => $photos,
            ));
    }
}
