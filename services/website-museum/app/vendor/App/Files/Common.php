<?php

class App_Files_Common
{
    public static function rehashAll()
    {
        Framework_Database::transact(function ($db) {
            $files = App_Models_File::where("1 ORDER BY `id`");
            foreach ($files as $file) {
                if ($source = $file->getSource()) {
                    $source = get_doc_path($source);
                    $data = file_get_contents($source);
                    $hash = md5($data);

                    if ($hash != $file["hash"]) {
                        try {
                            $db->query("UPDATE `files` SET `hash` = ? WHERE `id` = ?", array($hash, $file["id"]));
                        } catch (Framework_Errors_Duplicate $e) {
                            log_warning("file %u deleted -- is a duplicate.", $file["id"]);
                            $db->query("DELETE FROM `files` WHERE `id` = ?", array($file["id"]));
                        }
                    }
                }
            }
        });
    }
}
