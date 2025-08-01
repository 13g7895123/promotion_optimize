<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServersTable extends Migration
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
            'owner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'server_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'unique'     => true,
                'comment'    => 'Unique server identifier code',
            ],
            'server_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'game_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Game type (minecraft, cs2, etc.)',
            ],
            'version' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'website_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'discord_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'server_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'server_port' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'logo_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'background_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'banner_images' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of banner image URLs',
            ],
            'max_players' => [
                'type'       => 'INT',
                'constraint' => 6,
                'unsigned'   => true,
                'null'       => true,
            ],
            'online_players' => [
                'type'       => 'INT',
                'constraint' => 6,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'suspended', 'inactive'],
                'default'    => 'pending',
            ],
            'approval_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tags' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of server tags',
            ],
            'features' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of server features',
            ],
            'social_links' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Social media links object',
            ],
            'stats' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Server statistics data',
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Additional server metadata',
            ],
            'is_featured' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'featured_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_ping_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ping_status' => [
                'type'       => 'ENUM',
                'constraint' => ['online', 'offline', 'unknown'],
                'default'    => 'unknown',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
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
        $this->forge->addUniqueKey('server_code');
        $this->forge->addKey('owner_id');
        $this->forge->addKey('game_type');
        $this->forge->addKey('status');
        $this->forge->addKey('is_featured');
        $this->forge->addKey('sort_order');
        $this->forge->addKey('created_at');
        $this->forge->addKey('deleted_at');

        // Add foreign key constraints
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('servers');
    }

    public function down()
    {
        $this->forge->dropTable('servers');
    }
}