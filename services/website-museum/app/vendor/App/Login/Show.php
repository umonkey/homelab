<?php

class App_Login_Show extends App_Core_View
{
    public function onGet()
    {
        $args = $this->getArgs(array(
            "code" => null,
            ));

        if ($args["code"])
            return $this->onCode($args["code"]);

        $vk = new Framework_VK();
        $url = $vk->getLoginURL("status,photos");

        return $this->redirect($url);
    }

    protected function onCode($code)
    {
        $vk = new Framework_VK();
        $res = $vk->getToken($code);

        $s = $this->sessionRead();

        $s["vk"] = [
            "id" => $res["user_id"],
            "token" => $res["access_token"],
        ];

        $vkid = $res["user_id"];
        $admins = Framework_Config::get("vk_admins");
        if (in_array($vkid, $admins)) {
            $s["is_admin"] = true;
        }

        $s->save();

        return $this->redirect("/admin");
    }
}
