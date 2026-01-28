<?php

class App_Photo_Submit extends App_Core_View
{
    public function onGet()
    {
        $args = func_get_args();

        $template = "photo-submit.twig";

        $data = array(
            "tab" => "photo",
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/photo" => "Архив фотографий",
                "/photo/submit" => "Добавление",
                )),
            );

        if (count($args) > 0)
            $data["photo"] = App_Models_Photo::getById($args[0])->forTemplate();

        if (count($args) == 2)
            $template = "photo-submit-{$args[1]}.twig";

        return $this->sendPage($template, $data);
    }

    public function onPost()
    {
        $args = func_get_args();

        $id = @$args[0];
        $step = @$args[1];

        if (!$step)
            $step = $_POST["step"];

        switch ($step) {
        case "1":
            return $this->onPost1();
        case "2":
            return $this->onPost2($id);
        case "3":
            return $this->onPost3($id);
        case "4":
            return $this->onPost4($id);
        case "5":
            return $this->onPost5($id);
        case "6":
            return $this->onPost6($id);
        }

        return $this->sendJSON(array(
            "message" => "Не удалось обработать запрос.",
            ));

        debug($form);

        $files = $this->getFiles("photo");

        $now = strftime("%Y-%m-%d %H:%M:%S");
        $failed = $saved = 0;
        $lastid = null;

        foreach ($files as $finfo) {
            if ($finfo["error"] != 0) {
                // $this->logWarning("bad file upload: %s", var_export($finfo, 1));
                $failed++;
                continue;
            }

            if (!preg_match('@\.(jpg|jpeg|png)$@i', $finfo["name"])) {
                // $this->logWarning("bad file type: %s", var_export($finfo, 1));
                $failed++;
                continue;
            }

            $f = App_Models_Photo::fromRow(array(
                "created" => $now,
                "title" => $form["title"],
                "description" => $form["description"],
                "published" => 0,
                ));
            $f->save();

            $lastid = $f->id;

            $folder = get_doc_path("files/photos/{$f->id}");
            if (!is_dir($folder))
                mkdir($folder, 0755, true);

            $dst = $folder . "/old_o.jpg";
            move_uploaded_file($finfo["tmp_name"], $dst);

            $img = App_Core_Image::fromFile($dst);

            $img1 = $img->resizeMin(200, false);
            $img1->sharpen();
            $data = $img1->getJPEG(85);
            write_file("{$folder}/old_s.jpg", $data);

            $img1 = $img->resizeMax(1000, false);
            $data = $img1->getJPEG(75);
            write_file("{$folder}/old_l.jpg", $data);

            $saved++;

            // $this->logInfo("photo saved as %s", $dst);
        }

        if ($this->isAdmin()) {
            if ($saved == 1 and $lastid)
                $next = "/admin/photo/{$lastid}/edit?back=/photo/{$lastid}";
            else
                $next = "/admin/photo";

            return $this->sendJSON(array(
                "redirect" => $next,
                ));
        }

        if ($saved and $failed)
            $message = "Некоторые файлы не удалось обработать, остальные будут проверены оператором сайта и вскоре опубликованы.  Спасибо!";
        elseif ($saved)
            $message = "Файлы будут проверены оператором сайта и вскоре опубликованы.  Спасибо!";
        else
            $message = "Не удалось обработать ни один файл, извините.  Попробуйте приложить другие файлы.";

        return $this->sendJSON(array(
            "message" => $message,
            ));
    }

    protected function onPost1()
    {
        $files = $this->getFiles("photo");
        if (empty($files))
            return $this->fail("Вы не выбрали файл.");

        $file = $files[0];

        $photo = Framework_Database::transact(function ($db) use ($file) {
            $photo = App_Models_Photo::fromRow(array(
                "published" => 0,
                "title" => $file["name"],
                "created" => strftime('%Y-%m-%d %H:%M:%S'),
                ));
            $photo->save();

            $folder = get_doc_path("files/photos/{$photo["id"]}");
            if (!is_dir($folder))
                mkdir($folder, 0755, true);

            $dst = $folder . "/old_o.jpg";
            move_uploaded_file($file["tmp_name"], $dst);

            return $photo;
        });

        return $this->redirect("/photo/submit/{$photo["id"]}/step2");
    }

    protected function onPost2($id)
    {
        $form = $this->getForm2(array(
            "crop_data" => null,
            ));

        $photo = App_Models_Photo::getById($id);

        $images = $photo->findImages();
        $folder = dirname($images["old"]["o"]["path"]);
        $dst = $folder . "/crop.txt";

        if ($form["crop_data"])
            App_Util::writeFile($dst, $form["crop_data"]);
        elseif (is_readable($dst))
            @unlink($dst);

        $photo->refreshFiles();

        return $this->redirect("/photo/submit/{$photo["id"]}/step3");
    }

    protected function onPost3($id)
    {
        $form = $this->getForm2(array(
            "title" => null,
            "description" => null,
            ));

        $photo = App_Models_Photo::getById($id);
        if ($photo["published"])
            return $this->forbidden();

        $photo["title"] = $form["title"];
        $photo["description"] = $form["description"];
        $photo->save();

        return $this->redirect("/photo/submit/{$id}/step4");
    }

    protected function onPost4($id)
    {
        $photo = App_Models_Photo::getById($id);
        if ($photo["published"])
            return $this->forbidden();

        $form = $this->getForm2(array(
            "lat" => null,
            "lng" => null,
            "nolatlng" => null,
            ));

        if (!$form["nolatlng"] and $form["lat"] and $form["lng"]) {
            $photo["lat"] = $form["lat"];
            $photo["lng"] = $form["lng"];
            $photo->save();
        }

        return $this->redirect("/photo/submit/{$id}/step5");
    }

    protected function onPost5($id)
    {
        $photo = App_Models_Photo::getById($id);
        if ($photo["published"])
            return $this->forbidden();

        $files = $this->getFiles("photo");
        if ($files and $files[0]["error"] == 0) {
            $folder = get_doc_path("files/photos/{$photo["id"]}");
            $dst = $folder . "/new_o.jpg";
            move_uploaded_file($files[0]["tmp_name"], $dst);

            $photo->refreshFiles();
        }

        if (!$this->isAdmin())
            return $this->redirect("/photo?thanks=submit");

        return $this->redirect("/photo/submit/{$photo["id"]}/step6");
    }

    protected function onPost6($id)
    {
        $photo = App_Models_Photo::getById($id);
        if ($photo["published"])
            return $this->redirect("/admin/photo/{$photo["id"]}/edit");

        $form = $this->getForm2(array(
            "published" => null,
            ));

        if ($form["published"] == "yes") {
            $photo["published"] = true;
            $photo->save();
        }

        return $this->redirect("/admin/photo");
    }
}
