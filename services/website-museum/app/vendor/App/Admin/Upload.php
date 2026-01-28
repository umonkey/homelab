<?php

class App_Admin_Upload extends App_Core_View
{
    public function onPost()
    {
        $fail = false;
        $files = $this->request->files("files");

        $ids = array();
        foreach ($files as $file)
            if (!($id = $this->handleFile($file)))
                $fail = true;
            else
                $ids[] = $id;

        return $this->sendJSON(array(
            "redirect" => $fail ? "/admin/file?error=yes" : "/admin/file",
            "ids" => $ids,
            ));
    }

    protected function handleFile(array $file)
    {
        $good = array("image/jpeg", "image/png");
        if (!in_array($file["type"], $good))
            return false;

        if ($file["error"])
            return false;

        $data = file_get_contents($file["tmp_name"]);
        $hash = md5($data);

        if ($old = App_Models_File::getByHash($hash))
            return $old["id"];

        $f = new App_Models_File;
        $f["name"] = $file["name"];
        $f["length"] = filesize($file["tmp_name"]);
        $f["mime_type"] = $file["type"];
        $f["hash"] = $hash;
        $f->save();

        $this->logAction("Created file with id={id}.", [
            "type" => $type,
            "id" => $f->id,
        ]);

        $ext = mb_strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $dst = get_doc_path("files/{$f->id}/original.{$ext}");

        if (!is_dir($dir = dirname($dst))) {
            if (!@mkdir($dir, 0775, true)) {
                log_error("upload: error creating folder %s", $dir);
                return false;
            }
        }

        $res = @move_uploaded_file($file["tmp_name"], $dst);
        if (!$res) {
            log_error("upload: error saving %s", $dst);
            return false;
        }

        log_info("upload: file %s saved", $dst);

        App_Tasks_Thumbnails::queue(array($f->id));

        return $f["id"];
    }
}
