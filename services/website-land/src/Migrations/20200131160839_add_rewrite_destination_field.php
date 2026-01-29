<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddRewriteDestinationField extends AbstractMigration
{
    public function change()
    {
        $this->table('rewrite')
             ->addColumn('dst', 'string')
             ->update();
    }
}
