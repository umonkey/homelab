<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddRewriteTable extends AbstractMigration
{
    public function change()
    {
        $this->table('rewrite', ['id' => false, 'primary_key' => 'src'])
             ->addColumn('src', 'string')
             ->addColumn('node_id', 'integer', ['null' => false, 'signed' => false])
             ->addForeignKey('node_id', 'nodes', 'id', ['delete' => 'CASCADE'])
             ->addIndex(['node_id'])
             ->save();
    }
}
