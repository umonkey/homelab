<?php
/**
 * Basic VK API client.
 *
 * Login sequence:
 * (1) redirect to getLoginURL()
 * (2) pass code to getToken()
 **/

class Framework_VK
{
    protected $config;

    protected $token;

    public function __construct($token = null)
    {
        $c = Framework_Config::get("vk_oauth", array());

        if (empty($c["id"]) or empty($c["secret"]))
            throw new Framework_Errors_ServiceUnavailable("vk api not configured");

        $this->config = $c;

        $this->token = $token;
    }

    public function call($methodName, array $args = array(), $token = null)
    {
        if ($token === null)
            $token = $this->token;
        if ($token)
            $args["access_token"] = $token;

        $res = $this->fetchJSON("https://api.vk.com/method/{$methodName}", $args);

        if (!empty($res["error"]["error_msg"])) {
            log_debug("vk response: %s", var_export($res, 1));
            throw new RuntimeException($res["error"]["error_msg"]);
        }

        if (isset($res["response"]))
            return $res["response"];

        debug($res);
        return false;
    }

    /**
     * https://vk.com/dev/permissions
     **/
    public function getLoginURL($scope = "status")
    {
        $url = Framework_Util::buildURL("https://oauth.vk.com/authorize", array(
            "client_id" => $this->config["id"],
            "redirect_uri" => $this->getRedirectURI(),
            "display" => "page",
            "scope" => $scope,
            "response_type" => "code",
            ));

        return $url;
    }

    public function getToken($code)
    {
        $res = $this->fetchJSON("https://oauth.vk.com/access_token", array(
            "client_id" => $this->config["id"],
            "client_secret" => $this->config["secret"],
            "redirect_uri" => $this->getRedirectURI(),
            "code" => $code,
            ));
        if (empty($res["access_token"]))
            throw new RuntimeException("no access token");

        $this->token = $res["access_token"];
        return $res;
    }

    protected function getRedirectURI()
    {
        $back = Framework_Config::get("vk_return_uri");
        if ($back)
            return $back;

        $host = $_SERVER["HTTP_HOST"];

        $tmp = explode("?", $_SERVER["REQUEST_URI"]);
        $path = $tmp[0];

        $proto = "http";
        if (@$_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")
            $proto = "https";
        elseif (@$_SERVER["REQUEST_SCHEME"] == "https")
            $proto = "https";
        elseif (@$_SERVER["HTTPS"] == "on")
            $proto = "https";

        return "{$proto}://{$host}{$path}";
    }

    protected function fetchJSON($url, array $args)
    {
        $res = Framework_Util::post($url, $args);
        if (false === $res["data"])
            throw new RuntimeException("error calling vk api");

        switch ($res["status"]) {
        case 401:
		debug($url, $args, $res);
            throw new RuntimeException("bad token or auth code");
        }

        if ($res["status"] >= 400) {
            log_debug("request uri: %s", $url);
            log_debug("raw response: %s", var_export($res, 1));
            debug($url, $args, $res);
            throw new RuntimeException($res["status_text"]);
        }

        return json_decode($res["data"], true);
    }

    public function getProfileInfo()
    {
        $res = $this->call("account.getProfileInfo", [
            "v" => "5.92",
        ], $this->token);

        return $res;
    }

    public function uploadPhoto($path, array $args)
    {
        if (!function_exists("curl_init"))
            throw new Framework_Errors_ServiceUnavailable("curl not installed");

        if (!is_readable($path))
            throw new RuntimeException("photo is not readable");

        $args = array_merge(array(
            "album_id" => null,
            "caption" => null,
            "latitude" => null,
            "longitude" => null,
            ), $args);

        $res = $this->call("photos.getUploadServer", array(
            "album_id" => $args["album_id"],
            ));
        if (empty($res["upload_url"]))
            throw new Framework_Errors_ServiceUnavailable("no upload_url");

        log_debug("upload url: %s", $res["upload_url"]);
        log_debug("uploading file: %s", $path);

        $ch = curl_init($res["upload_url"]);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "file1" => curl_file_create($path),
            ));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: multipart/form-data; charset=UTF-8",
            ));

        $res = curl_exec($ch);
        curl_close($ch);

        if ($res === false)
            throw new Framework_Errors_ServiceUnavailable("bad upload response");

        $res = json_decode($res, true);

        $res = $this->call("photos.save", array(
            "album_id" => $args["album_id"],
            "server" => $res["server"],
            "photos_list" => $res["photos_list"],
            "hash" => $res["hash"],
            "latitude" => $args["latitude"],
            "longitude" => $args["longitude"],
            "caption" => $args["caption"],
            ));

        return $res;
    }
}
