<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolePermissionsTable extends Migration
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
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'permission_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        $this->forge->addUniqueKey(['role_id', 'permission_id']);
        $this->forge->addKey('role_id');
        $this->forge->addKey('permission_id');

        // Add foreign key constraints
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('role_permissions');

        // Assign permissions to default roles
        $this->assignDefaultPermissions();
    }

    private function assignDefaultPermissions()
    {
        // Get role and permission IDs
        $roles = $this->db->table('roles')->select('id, name')->get()->getResultArray();
        $permissions = $this->db->table('permissions')->select('id, name')->get()->getResultArray();

        $roleMap = array_column($roles, 'id', 'name');
        $permissionMap = array_column($permissions, 'id', 'name');

        $rolePermissions = [];

        // Super Admin - All permissions
        foreach ($permissionMap as $permName => $permId) {
            $rolePermissions[] = [
                'role_id' => $roleMap['super_admin'],
                'permission_id' => $permId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Admin - User and server management
        $adminPermissions = [
            'users.create', 'users.read', 'users.update', 'users.delete', 'users.manage',
            'servers.read', 'servers.update', 'servers.delete', 'servers.manage', 'servers.approve', 'servers.reject',
            'promotions.read', 'promotions.manage', 'promotions.review',
            'system.health', 'system.stats', 'system.logs',
        ];
        foreach ($adminPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $roleMap['admin'],
                    'permission_id' => $permissionMap[$permName],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Server Owner - Own server management
        $serverOwnerPermissions = [
            'servers.create', 'servers.read', 'servers.update',
            'promotions.create', 'promotions.read', 'promotions.update', 'promotions.participate',
        ];
        foreach ($serverOwnerPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $roleMap['server_owner'],
                    'permission_id' => $permissionMap[$permName],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Reviewer - Review permissions
        $reviewerPermissions = [
            'servers.read', 'servers.approve', 'servers.reject',
            'promotions.read', 'promotions.review',
        ];
        foreach ($reviewerPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $roleMap['reviewer'],
                    'permission_id' => $permissionMap[$permName],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        // User - Basic permissions
        $userPermissions = [
            'servers.read',
            'promotions.read', 'promotions.participate',
        ];
        foreach ($userPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $roleMap['user'],
                    'permission_id' => $permissionMap[$permName],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        if (!empty($rolePermissions)) {
            $this->db->table('role_permissions')->insertBatch($rolePermissions);
        }
    }

    public function down()
    {
        $this->forge->dropTable('role_permissions');
    }
}