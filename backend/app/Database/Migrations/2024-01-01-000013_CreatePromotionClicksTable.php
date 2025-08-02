<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromotionClicksTable extends Migration
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
            'promotion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'server_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'visitor_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'comment'    => '訪客IP',
            ],
            'visitor_fingerprint' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => '訪客指紋 (用於去重)',
            ],
            'user_agent' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'User Agent',
            ],
            'referrer_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => '來源網址',
            ],
            'landing_page' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => '落地頁面',
            ],
            'utm_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UTM來源',
            ],
            'utm_medium' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UTM媒介',
            ],
            'utm_campaign' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UTM活動',
            ],
            'utm_term' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UTM關鍵字',
            ],
            'utm_content' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UTM內容',
            ],
            'device_type' => [
                'type'       => 'ENUM',
                'constraint' => ['desktop', 'mobile', 'tablet', 'unknown'],
                'default'    => 'unknown',
                'comment'    => '設備類型',
            ],
            'browser' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => '瀏覽器',
            ],
            'os' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => '操作系統',
            ],
            'country' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true,
                'comment'    => '國家代碼',
            ],
            'region' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => '地區',
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => '城市',
            ],
            'is_unique' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => '是否唯一點擊',
            ],
            'is_converted' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => '是否已轉換',
            ],
            'converted_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '轉換用戶ID',
            ],
            'converted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '轉換時間',
            ],
            'session_duration' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '會話持續時間 (秒)',
            ],
            'metadata' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '額外追蹤資料',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['promotion_id', 'created_at']);
        $this->forge->addKey(['server_id', 'created_at']);
        $this->forge->addKey(['visitor_ip', 'created_at']);
        $this->forge->addKey(['visitor_fingerprint']);
        $this->forge->addKey(['is_unique', 'created_at']);
        $this->forge->addKey(['is_converted', 'converted_at']);
        $this->forge->addKey('created_at');

        // Foreign key constraints
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('server_id', 'servers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('converted_user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('promotion_clicks');
    }

    public function down()
    {
        $this->forge->dropTable('promotion_clicks');
    }
}