<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServerSettingsTable extends Migration
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
            'database_config' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Database connection configuration for rewards',
            ],
            'reward_table_mapping' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Mapping of reward tables and columns',
            ],
            'promotion_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Promotion configuration settings',
            ],
            'notification_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Email and LINE notification settings',
            ],
            'api_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'API keys and webhook configurations',
            ],
            'security_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Security and validation settings',
            ],
            'display_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Display and theme settings',
            ],
            'integration_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Third-party integration settings',
            ],
            'backup_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Backup and recovery settings',
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
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
        $this->forge->addUniqueKey('server_id');
        $this->forge->addKey('is_active');

        // Add foreign key constraint
        $this->forge->addForeignKey('server_id', 'servers', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('server_settings');
    }

    public function down()
    {
        $this->forge->dropTable('server_settings');
    }
}