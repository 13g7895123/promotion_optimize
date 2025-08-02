<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRewardsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => '獲得獎勵的用戶ID',
            ],
            'promotion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '相關推廣ID (可為空，如活動獎勵)',
            ],
            'reward_type' => [
                'type'       => 'ENUM',
                'constraint' => ['promotion', 'activity', 'checkin', 'referral', 'bonus'],
                'comment'    => '獎勵類型',
            ],
            'reward_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => '獎勵分類 (金幣、道具、經驗等)',
            ],
            'reward_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => '獎勵名稱',
            ],
            'reward_description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => '獎勵描述',
            ],
            'reward_amount' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => '獎勵數量',
            ],
            'reward_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'comment'    => '獎勵價值 (可選)',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'distributed', 'cancelled', 'failed'],
                'default'    => 'pending',
                'comment'    => '獎勵狀態',
            ],
            'priority' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 5,
                'comment'    => '處理優先級 (1-10)',
            ],
            'game_character' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => '遊戲角色名稱',
            ],
            'game_account' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => '遊戲帳號',
            ],
            'distribution_method' => [
                'type'       => 'ENUM',
                'constraint' => ['auto', 'manual', 'api', 'database'],
                'default'    => 'auto',
                'comment'    => '發放方式',
            ],
            'distribution_config' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '發放配置 (SQL指令、API參數等)',
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '審核者ID',
            ],
            'approved_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '審核時間',
            ],
            'distributed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '發放時間',
            ],
            'error_message' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => '錯誤訊息',
            ],
            'retry_count' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '重試次數',
            ],
            'metadata' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '額外資料',
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
        $this->forge->addKey(['server_id', 'user_id']);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey(['server_id', 'status']);
        $this->forge->addKey(['promotion_id']);
        $this->forge->addKey(['reward_type', 'status']);
        $this->forge->addKey('created_at');
        $this->forge->addKey('approved_at');
        $this->forge->addKey('distributed_at');
        $this->forge->addKey('deleted_at');
        $this->forge->addKey('priority');

        // Foreign key constraints
        $this->forge->addForeignKey('server_id', 'servers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('rewards');
    }

    public function down()
    {
        $this->forge->dropTable('rewards');
    }
}