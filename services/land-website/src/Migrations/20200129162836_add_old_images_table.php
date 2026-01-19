<?php

/**
 * Создание таблицы соответствия файлов.
 **/

use Phinx\Migration\AbstractMigration;

class AddOldImagesTable extends AbstractMigration
{
    public function change()
    {
        $this->table('old_files', ['id' => false, 'primary_key' => 'src'])
              ->addColumn('src', 'string')
              ->addColumn('hash', 'string')
              ->addColumn('dst', 'string')
              ->addIndex(['dst'])
              ->save();
    }
}
