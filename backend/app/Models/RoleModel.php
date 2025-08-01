<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'name', 'display_name', 'description', 'level', 'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.name,id,{id}]',
        'display_name' => 'required|min_length[3]|max_length[100]',
        'level' => 'required|integer|greater_than[0]|less_than[6]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Role name is required',
            'is_unique' => 'Role name already exists',
            'alpha_dash' => 'Role name can only contain letters, numbers, dashes, and underscores',
        ],
        'display_name' => [
            'required' => 'Display name is required',
        ],
        'level' => [
            'required' => 'Role level is required',
            'greater_than' => 'Role level must be between 1 and 5',
            'less_than' => 'Role level must be between 1 and 5',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    /**
     * Get role with permissions
     */
    public function getRoleWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        
        if (!$role) {
            return null;
        }

        $role['permissions'] = $this->getRolePermissions($roleId);

        return $role;
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions(int $roleId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('role_permissions rp')
                  ->join('permissions p', 'p.id = rp.permission_id')
                  ->where('rp.role_id', $roleId)
                  ->where('p.is_active', true)
                  ->select('p.id, p.name, p.display_name, p.description, p.resource, p.action')
                  ->orderBy('p.resource, p.action')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        $rolePermissionModel = new RolePermissionModel();
        
        // Check if already assigned
        $existing = $rolePermissionModel->where('role_id', $roleId)
                                       ->where('permission_id', $permissionId)
                                       ->first();

        if ($existing) {
            return true; // Already assigned
        }

        $data = [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ];

        return $rolePermissionModel->insert($data) !== false;
    }

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $rolePermissionModel = new RolePermissionModel();
        
        return $rolePermissionModel->where('role_id', $roleId)
                                  ->where('permission_id', $permissionId)
                                  ->delete();
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(int $roleId, string $permissionName): bool
    {
        $db = \Config\Database::connect();
        
        $result = $db->table('role_permissions rp')
                     ->join('permissions p', 'p.id = rp.permission_id')
                     ->where('rp.role_id', $roleId)
                     ->where('p.name', $permissionName)
                     ->where('p.is_active', true)
                     ->countAllResults();

        return $result > 0;
    }

    /**
     * Get users with this role
     */
    public function getRoleUsers(int $roleId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('user_roles ur')
                  ->join('users u', 'u.id = ur.user_id')
                  ->where('ur.role_id', $roleId)
                  ->where('ur.is_active', true)
                  ->where('u.deleted_at', null)
                  ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                  ->select('u.id, u.username, u.email, u.first_name, u.last_name, ur.assigned_at, ur.expires_at')
                  ->orderBy('ur.assigned_at', 'DESC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get roles by level (hierarchy)
     */
    public function getRolesByLevel(int $minLevel = 1, int $maxLevel = 5): array
    {
        return $this->where('level >=', $minLevel)
                    ->where('level <=', $maxLevel)
                    ->where('is_active', true)
                    ->orderBy('level', 'ASC')
                    ->findAll();
    }

    /**
     * Get roles that user can assign (based on their highest role level)
     */
    public function getAssignableRoles(int $userLevel): array
    {
        // Users can only assign roles with level greater than or equal to their own
        return $this->where('level >=', $userLevel)
                    ->where('is_active', true)
                    ->orderBy('level', 'ASC')
                    ->findAll();
    }

    /**
     * Sync permissions for role
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $db = \Config\Database::connect();
        
        try {
            $db->transStart();

            // Remove existing permissions
            $rolePermissionModel = new RolePermissionModel();
            $rolePermissionModel->where('role_id', $roleId)->delete();

            // Add new permissions
            if (!empty($permissionIds)) {
                $data = [];
                foreach ($permissionIds as $permissionId) {
                    $data[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
                $rolePermissionModel->insertBatch($data);
            }

            $db->transComplete();

            return $db->transStatus();

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error syncing role permissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role statistics
     */
    public function getRoleStats(int $roleId): array
    {
        $db = \Config\Database::connect();
        
        // Count active users with this role
        $activeUsers = $db->table('user_roles ur')
                          ->join('users u', 'u.id = ur.user_id')
                          ->where('ur.role_id', $roleId)
                          ->where('ur.is_active', true)
                          ->where('u.status', 'active')
                          ->where('u.deleted_at', null)
                          ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                          ->countAllResults();

        // Count permissions
        $permissions = $db->table('role_permissions rp')
                          ->join('permissions p', 'p.id = rp.permission_id')
                          ->where('rp.role_id', $roleId)
                          ->where('p.is_active', true)
                          ->countAllResults();

        // Get recent assignments
        $recentAssignments = $db->table('user_roles ur')
                                ->join('users u', 'u.id = ur.user_id')
                                ->where('ur.role_id', $roleId)
                                ->where('ur.assigned_at >', date('Y-m-d H:i:s', strtotime('-30 days')))
                                ->countAllResults();

        return [
            'active_users' => $activeUsers,
            'permissions_count' => $permissions,
            'recent_assignments' => $recentAssignments,
        ];
    }

    /**
     * Check if role can be deleted
     */
    public function canDelete(int $roleId): bool
    {
        // Don't allow deletion of system roles
        $systemRoles = ['super_admin', 'admin', 'user'];
        $role = $this->find($roleId);
        
        if (!$role || in_array($role['name'], $systemRoles)) {
            return false;
        }

        // Check if role has active users
        $db = \Config\Database::connect();
        $activeUsers = $db->table('user_roles')
                          ->where('role_id', $roleId)
                          ->where('is_active', true)
                          ->where('(expires_at IS NULL OR expires_at > NOW())')
                          ->countAllResults();

        return $activeUsers === 0;
    }
}