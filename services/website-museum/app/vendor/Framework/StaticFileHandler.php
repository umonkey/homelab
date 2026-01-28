<?php
/**
 * Handle static files.
 *
 * Use this directly in your route config, e.g.:
 * array('@^/(static/.+)$@', 'GET', 'Framework_StaticFileHandler'),
 **/

class Framework_StaticFileHandler extends Framework_View
{
    public function handle()
    {
        $fn = func_get_arg(0);
        if (!is_readable($fn)) {
            return new Framework_Response("File not found.", "404 Not Found", array(
                "Content-Type" => "text/plain",
                ));
        }

        $ct = $this->getFileType($fn);

        $data = file_get_contents($fn);
        $mtime = filemtime($fn);

        return new Framework_Response($data, "200 OK", array(
            "Content-Type" => $ct,
            ), $mtime);
    }

    protected function getFileType($fn)
    {
        $map = array("css" => "text/css; charset=utf-8",
                     "js" => "application/javascript; charset=utf-8",
                     "txt" => "text/plain; charset=utf-8",
                     "ico" => "image/x-icon",
                     "png" => "image/png",
                     "jpg" => "image/jpeg",
                     "gif" => "image/gif");

        $ext = pathinfo($fn, PATHINFO_EXTENSION);
        if (!($ct = @$map[$ext]))
            $ct = "application/octet-stream";

        return $ct;
    }

    public function prepare()
    {
    }

    public function complete()
    {
    }
}
