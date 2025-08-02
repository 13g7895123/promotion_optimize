<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromotionStatsTable extends Migration
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
            'promoter_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'stat_date' => [
                'type'    => 'DATE',
                'comment' => '統計日期',
            ],
            'stat_type' => [
                'type'       => 'ENUM',
                'constraint' => ['daily', 'weekly', 'monthly', 'yearly'],
                'default'    => 'daily',
                'comment'    => '統計類型',
            ],
            'clicks' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '點擊數',
            ],
            'unique_clicks' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '唯一點擊數',
            ],
            'conversions' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '轉換數',
            ],
            'rewards_earned' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '獲得獎勵數量',
            ],
            'revenue_generated' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => '0.00',
                'comment'    => '產生收益 (如果追蹤)',
            ],
            'conversion_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,4',
                'default'    => '0.0000',
                'comment'    => '轉換率',
            ],
            'metadata' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '額外統計資料',
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
        $this->forge->addUniqueKey(['promotion_id', 'stat_date', 'stat_type']);
        $this->forge->addKey(['server_id', 'stat_date']);
        $this->forge->addKey(['promoter_id', 'stat_date']);
        $this->forge->addKey('stat_date');
        $this->forge->addKey('stat_type');

        // Foreign key constraints
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('server_id', 'servers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('promoter_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('promotion_stats');
    }

    public function down()
    {
        $this->forge->dropTable('promotion_stats');
    }
}