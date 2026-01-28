<?php

class App_Map extends App_Core_View
{
    public function onGet()
    {
        $data = array(
            "tab" => "places",
            "body_class" => "places",
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/places" => "Места",
                )),
            "map_data" => $this->getMapData(),
            );

        return $this->sendPage("places.twig", $data);
    }

    protected function getMapData()
    {
        $res = array();

        $minlat = $maxlat = null;
        $minlng = $maxlng = null;

        $places = App_Models_Place::where("`published` = 1 AND `lat` IS NOT NULL AND `lng` IS NOT NULL");
        foreach ($places as $place) {
            $lat = floatval($place["lat"]);
            $lng = floatval($place["lng"]);

            $res[] = array(
                "latlng" => array(floatval($place["lat"]), floatval($place["lng"])),
                "html" => $place->getMapHTML(),
                );
        }

        return json_encode(array(
            "places" => $res,
            ));
    }
}
