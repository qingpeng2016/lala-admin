<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddStatusToSystemNewTblhostingNotes extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('system_new_tblhosting_notes');
        
        // 添加status字段
        $table->addColumn('status', 'string', [
            'limit' => 10,
            'default' => 'Wait',
            'comment' => '状态：Wait-待处理，Deal-已处理',
            'after' => 'adjust_amount'
        ])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->update();
    }
}
