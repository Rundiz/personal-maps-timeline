<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNewGooglePlacesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // create the table
        $table = $this->table('google_places',
            [
                'id' => false,
                'primary_key' => ['place_id'],
            ]
        );
        $table->addColumn('place_id', 'string', 
                [
                    'limit' => 255,
                ]
            )
            ->addColumn('place_name', 'string', 
                [
                    'limit' => 255, 
                    'default' => null, 
                    'null' => true,
                ]
            )
            ->create();
    }
}
