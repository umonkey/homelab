<?php

class App_Tasks_Thumbnails extends Framework_TaskHandler
{
    public function handle(array $args)
    {
        $id = $args[0];

        $file = App_Models_File::getById($id, true);
        if (!$file) {
            log_warning("thumbnailer: file %s does not exist.", $id);
            return;
        }

        log_info("thumbnailer: processing file %u", $id);

        $src = get_doc_path($file->getSource());
        if (!is_readable($src)) {
            log_warning("thumbnailer: file %s does not exist.", $src);
            return;
        }

        $img = App_Core_Image::fromFile($src);

        $t = $img->resizeMax(1000, false);
        $dst = get_doc_path("files/{$file->id}/lg.jpg");
        write_file($dst, $t->getJPEG(85));

        $t = $img->resizeMin(300, false);
        $t->sharpen();
        $dst = get_doc_path("files/{$file->id}/md.jpg");
        write_file($dst, $t->getJPEG(85));
    }
}
