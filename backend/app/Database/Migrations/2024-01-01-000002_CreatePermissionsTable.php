<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionsTable extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'resource' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Resource name (users, servers, promotions, etc.)',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Action type (create, read, update, delete, manage)',
            ],
            'is_active' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
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
        $this->forge->addKey('name');
        $this->forge->addKey(['resource', 'action']);
        $this->forge->createTable('permissions');

        // Insert default permissions
        $permissions = [
            // User management permissions
            ['users', 'create', 'Create Users', 'Create new user accounts'],
            ['users', 'read', 'Read Users', 'View user information'],
            ['users', 'update', 'Update Users', 'Edit user information'],
            ['users', 'delete', 'Delete Users', 'Delete user accounts'],
            ['users', 'manage', 'Manage Users', 'Full user management access'],

            // Role and permission management
            ['roles', 'create', 'Create Roles', 'Create new roles'],
            ['roles', 'read', 'Read Roles', 'View role information'],
            ['roles', 'update', 'Update Roles', 'Edit role information'],
            ['roles', 'delete', 'Delete Roles', 'Delete roles'],
            ['roles', 'manage', 'Manage Roles', 'Full role management access'],

            ['permissions', 'create', 'Create Permissions', 'Create new permissions'],
            ['permissions', 'read', 'Read Permissions', 'View permission information'],
            ['permissions', 'update', 'Update Permissions', 'Edit permission information'],
            ['permissions', 'delete', 'Delete Permissions', 'Delete permissions'],
            ['permissions', 'manage', 'Manage Permissions', 'Full permission management access'],

            // Server management permissions
            ['servers', 'create', 'Create Servers', 'Register new servers'],
            ['servers', 'read', 'Read Servers', 'View server information'],
            ['servers', 'update', 'Update Servers', 'Edit server information'],
            ['servers', 'delete', 'Delete Servers', 'Delete servers'],
            ['servers', 'manage', 'Manage Servers', 'Full server management access'],
            ['servers', 'approve', 'Approve Servers', 'Approve server registration'],
            ['servers', 'reject', 'Reject Servers', 'Reject server registration'],

            // Promotion management permissions
            ['promotions', 'create', 'Create Promotions', 'Create promotion campaigns'],
            ['promotions', 'read', 'Read Promotions', 'View promotion information'],
            ['promotions', 'update', 'Update Promotions', 'Edit promotion campaigns'],
            ['promotions', 'delete', 'Delete Promotions', 'Delete promotion campaigns'],
            ['promotions', 'manage', 'Manage Promotions', 'Full promotion management access'],
            ['promotions', 'participate', 'Participate Promotions', 'Participate in promotions'],
            ['promotions', 'review', 'Review Promotions', 'Review promotion submissions'],

            // System management permissions
            ['system', 'health', 'System Health', 'View system health information'],
            ['system', 'stats', 'System Statistics', 'View system statistics'],
            ['system', 'logs', 'System Logs', 'View system logs'],
            ['system', 'manage', 'System Management', 'Full system management access'],
        ];

        $data = [];
        foreach ($permissions as $perm) {
            $data[] = [
                'name' => $perm[0] . '.' . $perm[1],
                'resource' => $perm[0],
                'action' => $perm[1],
                'display_name' => $perm[2],
                'description' => $perm[3],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->table('permissions')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('permissions');
    }
}