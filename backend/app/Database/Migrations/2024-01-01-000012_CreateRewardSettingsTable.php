<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRewardSettingsTable extends Migration
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
            'setting_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => '設定名稱',
            ],
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => '設定鍵值',
            ],
            'setting_type' => [
                'type'       => 'ENUM',
                'constraint' => ['promotion', 'activity', 'checkin', 'general'],
                'comment'    => '設定類型',
            ],
            'reward_type' => [
                'type'       => 'ENUM',
                'constraint' => ['promotion', 'activity', 'checkin', 'referral', 'bonus'],
                'comment'    => '獎勵類型',
            ],
            'trigger_condition' => [
                'type'    => 'JSON',
                'comment' => '觸發條件配置',
            ],
            'reward_config' => [
                'type'    => 'JSON',
                'comment' => '獎勵配置 (類型、數量、名稱等)',
            ],
            'limits_config' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '限制配置 (每日/每週/每月上限等)',
            ],
            'distribution_config' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '發放配置 (自動/手動、資料庫連接等)',
            ],
            'auto_approve' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => '是否自動審核',
            ],
            'auto_distribute' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => '是否自動發放',
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'comment' => '是否啟用',
            ],
            'priority' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 5,
                'comment'    => '處理優先級 (1-10)',
            ],
            'valid_from' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '生效開始時間',
            ],
            'valid_until' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '生效結束時間',
            ],
            'usage_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '使用次數統計',
            ],
            'success_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '成功發放次數',
            ],
            'error_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '錯誤次數',
            ],
            'last_used_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '最後使用時間',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => '設定描述',
            ],
            'metadata' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '額外設定資料',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '創建者ID',
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '最後更新者ID',
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
        $this->forge->addUniqueKey(['server_id', 'setting_key', 'deleted_at']);
        $this->forge->addKey(['server_id', 'setting_type']);
        $this->forge->addKey(['server_id', 'reward_type']);
        $this->forge->addKey(['server_id', 'is_active']);
        $this->forge->addKey(['setting_type', 'is_active']);
        $this->forge->addKey('valid_from');
        $this->forge->addKey('valid_until');
        $this->forge->addKey('priority');
        $this->forge->addKey('created_at');
        $this->forge->addKey('deleted_at');

        // Foreign key constraints
        $this->forge->addForeignKey('server_id', 'servers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('reward_settings');
    }

    public function down()
    {
        $this->forge->dropTable('reward_settings');
    }
}