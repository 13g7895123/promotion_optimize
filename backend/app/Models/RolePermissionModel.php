<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table = 'role_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'role_id', 'permission_id'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'role_id' => 'required|integer|is_not_unique[roles.id]',
        'permission_id' => 'required|integer|is_not_unique[permissions.id]',
    ];

    protected $validationMessages = [
        'role_id' => [
            'required' => 'Role ID is required',
            'is_not_unique' => 'Role does not exist',
        ],
        'permission_id' => [
            'required' => 'Permission ID is required',
            'is_not_unique' => 'Permission does not exist',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    /**
     * Get role permissions with details
     */
    public function getRolePermissionsWithDetails(int $roleId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' rp')
                  ->join('permissions p', 'p.id = rp.permission_id')
                  ->where('rp.role_id', $roleId)
                  ->where('p.is_active', true)
                  ->select('
                      rp.id,
                      rp.role_id,
                      rp.permission_id,
                      rp.created_at,
                      p.name as permission_name,
                      p.display_name as permission_display_name,
                      p.description as permission_description,
                      p.resource,
                      p.action
                  ')
                  ->orderBy('p.resource, p.action')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get permission roles with details
     */
    public function getPermissionRolesWithDetails(int $permissionId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' rp')
                  ->join('roles r', 'r.id = rp.role_id')
                  ->where('rp.permission_id', $permissionId)
                  ->where('r.is_active', true)
                  ->select('
                      rp.id,
                      rp.role_id,
                      rp.permission_id,
                      rp.created_at,
                      r.name as role_name,
                      r.display_name as role_display_name,
                      r.description as role_description,
                      r.level as role_level
                  ')
                  ->orderBy('r.level, r.name')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Check if role has permission
     */
    public function roleHasPermission(int $roleId, int $permissionId): bool
    {
        $result = $this->where('role_id', $roleId)
                       ->where('permission_id', $permissionId)
                       ->countAllResults();

        return $result > 0;
    }

    /**
     * Get all permissions for multiple roles
     */
    public function getMultipleRolePermissions(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' rp')
                  ->join('permissions p', 'p.id = rp.permission_id')
                  ->whereIn('rp.role_id', $roleIds)
                  ->where('p.is_active', true)
                  ->select('p.name')
                  ->distinct()
                  ->get()
                  ->getResultArray();
    }

    /**
     * Sync permissions for role (remove all and add new ones)
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Remove existing permissions
            $this->where('role_id', $roleId)->delete();

            // Add new permissions
            if (!empty($permissionIds)) {
                $data = [];
                $now = date('Y-m-d H:i:s');

                foreach ($permissionIds as $permissionId) {
                    $data[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $this->insertBatch($data);
            }

            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {
            log_message('error', 'Error syncing role permissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Copy permissions from one role to another
     */
    public function copyRolePermissions(int $fromRoleId, int $toRoleId): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Get source role permissions
            $sourcePermissions = $this->where('role_id', $fromRoleId)->findAll();

            if (!empty($sourcePermissions)) {
                $data = [];
                $now = date('Y-m-d H:i:s');

                foreach ($sourcePermissions as $permission) {
                    // Check if target role already has this permission
                    $existing = $this->where('role_id', $toRoleId)
                                     ->where('permission_id', $permission['permission_id'])
                                     ->first();

                    if (!$existing) {
                        $data[] = [
                            'role_id' => $toRoleId,
                            'permission_id' => $permission['permission_id'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($data)) {
                    $this->insertBatch($data);
                }
            }

            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {
            log_message('error', 'Error copying role permissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get permissions matrix (roles vs permissions)
     */
    public function getPermissionsMatrix(): array
    {
        $db = \Config\Database::connect();
        
        // Get all active roles
        $roles = $db->table('roles')
                    ->where('is_active', true)
                    ->orderBy('level, name')
                    ->get()
                    ->getResultArray();

        // Get all active permissions grouped by resource
        $permissions = $db->table('permissions')
                          ->where('is_active', true)
                          ->orderBy('resource, action')
                          ->get()
                          ->getResultArray();

        // Get all role-permission relationships
        $rolePermissions = $this->select('role_id, permission_id')->findAll();
        
        // Create lookup array for faster access
        $lookup = [];
        foreach ($rolePermissions as $rp) {
            $lookup[$rp['role_id']][$rp['permission_id']] = true;
        }

        // Build matrix
        $matrix = [
            'roles' => $roles,
            'permissions' => $permissions,
            'assignments' => $lookup,
        ];

        return $matrix;
    }

    /**
     * Get role permission statistics
     */
    public function getRolePermissionStats(int $roleId): array
    {
        $db = \Config\Database::connect();
        
        // Total permissions for this role
        $totalPermissions = $this->where('role_id', $roleId)->countAllResults();

        // Permissions by resource
        $permissionsByResource = $db->table($this->table . ' rp')
                                    ->join('permissions p', 'p.id = rp.permission_id')
                                    ->where('rp.role_id', $roleId)
                                    ->where('p.is_active', true)
                                    ->groupBy('p.resource')
                                    ->select('p.resource, COUNT(*) as count')
                                    ->get()
                                    ->getResultArray();

        // Users affected by this role's permissions
        $affectedUsers = $db->table('user_roles ur')
                            ->join('users u', 'u.id = ur.user_id')
                            ->where('ur.role_id', $roleId)
                            ->where('ur.is_active', true)
                            ->where('u.status', 'active')
                            ->where('u.deleted_at', null)
                            ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                            ->countAllResults();

        return [
            'total_permissions' => $totalPermissions,
            'permissions_by_resource' => $permissionsByResource,
            'affected_users' => $affectedUsers,
        ];
    }

    /**
     * Get permission role statistics
     */
    public function getPermissionRoleStats(int $permissionId): array
    {
        $db = \Config\Database::connect();
        
        // Total roles with this permission
        $totalRoles = $this->where('permission_id', $permissionId)->countAllResults();

        // Roles by level
        $rolesByLevel = $db->table($this->table . ' rp')
                           ->join('roles r', 'r.id = rp.role_id')
                           ->where('rp.permission_id', $permissionId)
                           ->where('r.is_active', true)
                           ->groupBy('r.level')
                           ->select('r.level, COUNT(*) as count')
                           ->orderBy('r.level')
                           ->get()
                           ->getResultArray();

        // Total users with this permission (through roles)
        $totalUsers = $db->table($this->table . ' rp')
                         ->join('user_roles ur', 'ur.role_id = rp.role_id')
                         ->join('users u', 'u.id = ur.user_id')
                         ->where('rp.permission_id', $permissionId)
                         ->where('ur.is_active', true)
                         ->where('u.status', 'active')
                         ->where('u.deleted_at', null)
                         ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                         ->countAllResults();

        return [
            'total_roles' => $totalRoles,
            'roles_by_level' => $rolesByLevel,
            'total_users' => $totalUsers,
        ];
    }

    /**
     * Find roles with specific permission pattern
     */
    public function findRolesWithPermissionPattern(string $resource, ?string $action = null): array
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table($this->table . ' rp')
                      ->join('roles r', 'r.id = rp.role_id')
                      ->join('permissions p', 'p.id = rp.permission_id')
                      ->where('r.is_active', true)
                      ->where('p.is_active', true)
                      ->where('p.resource', $resource);

        if ($action) {
            $builder->where('p.action', $action);
        }

        return $builder->select('
                    r.id as role_id,
                    r.name as role_name,
                    r.display_name as role_display_name,
                    r.level as role_level,
                    p.id as permission_id,
                    p.name as permission_name,
                    p.action
                ')
                ->orderBy('r.level, r.name')
                ->get()
                ->getResultArray();
    }

    /**
     * Bulk remove permissions from role
     */
    public function bulkRemovePermissions(int $roleId, array $permissionIds): bool
    {
        if (empty($permissionIds)) {
            return true;
        }

        return $this->where('role_id', $roleId)
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
    }

    /**
     * Bulk add permissions to role
     */
    public function bulkAddPermissions(int $roleId, array $permissionIds): bool
    {
        if (empty($permissionIds)) {
            return true;
        }

        $data = [];
        $now = date('Y-m-d H:i:s');

        foreach ($permissionIds as $permissionId) {
            // Check if already exists
            if (!$this->roleHasPermission($roleId, $permissionId)) {
                $data[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($data)) {
            return true; // Nothing to insert
        }

        return $this->insertBatch($data) !== false;
    }
}