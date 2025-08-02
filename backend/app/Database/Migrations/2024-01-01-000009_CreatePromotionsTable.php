<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromotionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'server_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'promoter_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => '推廣者用戶ID',
            ],
            'promoted_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '被推廣用戶ID (註冊後才有)',
            ],
            'promotion_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'unique'     => true,
                'comment'    => '唯一推廣代碼',
            ],
            'promotion_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'comment'    => '完整推廣連結',
            ],
            'click_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '點擊次數',
            ],
            'unique_click_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '唯一點擊次數',
            ],
            'conversion_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '轉換次數 (註冊成功)',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'paused', 'expired', 'banned'],
                'default'    => 'active',
                'comment'    => '推廣狀態',
            ],
            'source_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'comment'    => '創建推廣時的IP',
            ],
            'user_agent' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => '創建推廣時的User Agent',
            ],
            'referrer_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => '來源網址',
            ],
            'expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '過期時間',
            ],
            'last_clicked_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '最後點擊時間',
            ],
            'metadata' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '額外資料 (UTM參數等)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('promotion_code');
        $this->forge->addKey(['server_id', 'promoter_id']);
        $this->forge->addKey(['promoter_id', 'status']);
        $this->forge->addKey(['server_id', 'status']);
        $this->forge->addKey('created_at');
        $this->forge->addKey('expires_at');
        $this->forge->addKey('deleted_at');

        // Foreign key constraints
        $this->forge->addForeignKey('server_id', 'servers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('promoter_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('promoted_user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('promotions');
    }

    public function down()
    {
        $this->forge->dropTable('promotions');
    }
}