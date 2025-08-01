<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'name', 'display_name', 'description', 'resource', 'action', 'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|alpha_dash|min_length[3]|max_length[100]|is_unique[permissions.name,id,{id}]',
        'display_name' => 'required|min_length[3]|max_length[150]',
        'resource' => 'required|alpha_dash|min_length[3]|max_length[50]',
        'action' => 'required|alpha_dash|min_length[3]|max_length[20]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Permission name is required',
            'is_unique' => 'Permission name already exists',
            'alpha_dash' => 'Permission name can only contain letters, numbers, dashes, and underscores',
        ],
        'display_name' => [
            'required' => 'Display name is required',
        ],
        'resource' => [
            'required' => 'Resource is required',
            'alpha_dash' => 'Resource can only contain letters, numbers, dashes, and underscores',
        ],
        'action' => [
            'required' => 'Action is required',
            'alpha_dash' => 'Action can only contain letters, numbers, dashes, and underscores',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generateName'];
    protected $beforeUpdate = ['generateName'];

    /**
     * Generate permission name from resource and action
     */
    protected function generateName(array $data)
    {
        if (isset($data['data']['resource']) && isset($data['data']['action']) && !isset($data['data']['name'])) {
            $data['data']['name'] = $data['data']['resource'] . '.' . $data['data']['action'];
        }

        return $data;
    }

    /**
     * Get permissions grouped by resource
     */
    public function getPermissionsByResource(): array
    {
        $permissions = $this->where('is_active', true)
                           ->orderBy('resource, action')
                           ->findAll();

        $grouped = [];
        foreach ($permissions as $permission) {
            $resource = $permission['resource'];
            if (!isset($grouped[$resource])) {
                $grouped[$resource] = [];
            }
            $grouped[$resource][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get roles that have this permission
     */
    public function getPermissionRoles(int $permissionId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('role_permissions rp')
                  ->join('roles r', 'r.id = rp.role_id')
                  ->where('rp.permission_id', $permissionId)
                  ->where('r.is_active', true)
                  ->select('r.id, r.name, r.display_name, r.level')
                  ->orderBy('r.level', 'ASC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get users that have this permission (through roles)
     */
    public function getPermissionUsers(int $permissionId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('role_permissions rp')
                  ->join('user_roles ur', 'ur.role_id = rp.role_id')
                  ->join('users u', 'u.id = ur.user_id')
                  ->where('rp.permission_id', $permissionId)
                  ->where('ur.is_active', true)
                  ->where('u.status', 'active')
                  ->where('u.deleted_at', null)
                  ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                  ->select('u.id, u.username, u.email, u.first_name, u.last_name')
                  ->distinct()
                  ->orderBy('u.username')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get available resources
     */
    public function getResources(): array
    {
        return $this->select('resource')
                    ->distinct()
                    ->where('is_active', true)
                    ->orderBy('resource')
                    ->findColumn('resource');
    }

    /**
     * Get available actions
     */
    public function getActions(): array
    {
        return $this->select('action')
                    ->distinct()
                    ->where('is_active', true)
                    ->orderBy('action')
                    ->findColumn('action');
    }

    /**
     * Get permissions for specific resource
     */
    public function getResourcePermissions(string $resource): array
    {
        return $this->where('resource', $resource)
                    ->where('is_active', true)
                    ->orderBy('action')
                    ->findAll();
    }

    /**
     * Check if permission exists
     */
    public function permissionExists(string $resource, string $action): bool
    {
        $result = $this->where('resource', $resource)
                       ->where('action', $action)
                       ->countAllResults();

        return $result > 0;
    }

    /**
     * Create permission if not exists
     */
    public function createIfNotExists(string $resource, string $action, string $displayName, ?string $description = null): int
    {
        $existing = $this->where('resource', $resource)
                         ->where('action', $action)
                         ->first();

        if ($existing) {
            return $existing['id'];
        }

        $data = [
            'name' => $resource . '.' . $action,
            'display_name' => $displayName,
            'description' => $description,
            'resource' => $resource,
            'action' => $action,
            'is_active' => true,
        ];

        $id = $this->insert($data);
        return $id ? $this->getInsertID() : 0;
    }

    /**
     * Bulk create permissions
     */
    public function createBulk(array $permissions): bool
    {
        $data = [];
        $now = date('Y-m-d H:i:s');

        foreach ($permissions as $perm) {
            // Check if already exists
            if (!$this->permissionExists($perm['resource'], $perm['action'])) {
                $data[] = [
                    'name' => $perm['resource'] . '.' . $perm['action'],
                    'display_name' => $perm['display_name'],
                    'description' => $perm['description'] ?? null,
                    'resource' => $perm['resource'],
                    'action' => $perm['action'],
                    'is_active' => true,
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

    /**
     * Get permission statistics
     */
    public function getPermissionStats(int $permissionId): array
    {
        $db = \Config\Database::connect();
        
        // Count roles with this permission
        $rolesCount = $db->table('role_permissions rp')
                         ->join('roles r', 'r.id = rp.role_id')
                         ->where('rp.permission_id', $permissionId)
                         ->where('r.is_active', true)
                         ->countAllResults();

        // Count users with this permission (through roles)
        $usersCount = $db->table('role_permissions rp')
                         ->join('user_roles ur', 'ur.role_id = rp.role_id')
                         ->join('users u', 'u.id = ur.user_id')
                         ->where('rp.permission_id', $permissionId)
                         ->where('ur.is_active', true)
                         ->where('u.status', 'active')
                         ->where('u.deleted_at', null)
                         ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                         ->countAllResults();

        return [
            'roles_count' => $rolesCount,
            'users_count' => $usersCount,
        ];
    }

    /**
     * Check if permission can be deleted
     */
    public function canDelete(int $permissionId): bool
    {
        // Don't allow deletion of core system permissions
        $corePermissions = [
            'users.read', 'users.update', 'servers.read', 'servers.create',
            'promotions.read', 'promotions.participate', 'system.health'
        ];
        
        $permission = $this->find($permissionId);
        
        if (!$permission || in_array($permission['name'], $corePermissions)) {
            return false;
        }

        // Check if permission is assigned to any role
        $db = \Config\Database::connect();
        $roleCount = $db->table('role_permissions')
                        ->where('permission_id', $permissionId)
                        ->countAllResults();

        return $roleCount === 0;
    }

    /**
     * Search permissions
     */
    public function searchPermissions(string $query, array $filters = []): array
    {
        $builder = $this->builder();

        // Apply search
        if (!empty($query)) {
            $builder->groupStart()
                    ->like('name', $query)
                    ->orLike('display_name', $query)
                    ->orLike('description', $query)
                    ->orLike('resource', $query)
                    ->orLike('action', $query)
                    ->groupEnd();
        }

        // Apply filters
        if (!empty($filters['resource'])) {
            $builder->where('resource', $filters['resource']);
        }

        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        return $builder->orderBy('resource, action')
                      ->findAll();
    }
}