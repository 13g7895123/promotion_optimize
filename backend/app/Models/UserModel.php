<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'username', 'email', 'password_hash', 'first_name', 'last_name', 
        'avatar', 'phone', 'line_id', 'discord_id', 'status', 
        'email_verified_at', 'last_login_at', 'last_login_ip', 
        'failed_login_attempts', 'locked_until', 'password_reset_token', 
        'password_reset_expires', 'email_verification_token',
        'two_factor_secret', 'two_factor_enabled', 'preferences', 'metadata'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'username' => 'required|alpha_numeric_punct|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]',
        'email' => 'required|valid_email|max_length[100]|is_unique[users.email,id,{id}]',
        'password_hash' => 'permit_empty|min_length[8]',
        'first_name' => 'permit_empty|alpha_space|max_length[50]',
        'last_name' => 'permit_empty|alpha_space|max_length[50]',
        'phone' => 'permit_empty|numeric|max_length[20]',
        'status' => 'permit_empty|in_list[active,inactive,suspended,pending]',
    ];

    protected $validationMessages = [
        'username' => [
            'required' => 'Username is required',
            'is_unique' => 'Username already exists',
            'min_length' => 'Username must be at least 3 characters',
            'max_length' => 'Username cannot exceed 50 characters',
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please provide a valid email address',
            'is_unique' => 'Email already exists',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash password before saving
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            unset($data['data']['password']);
        }

        return $data;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Get user with roles
     */
    public function getUserWithRoles(int $userId): ?array
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return null;
        }

        $user['roles'] = $this->getUserRoles($userId);
        $user['permissions'] = $this->getUserPermissions($userId);

        return $user;
    }

    /**
     * Get user roles
     */
    public function getUserRoles(int $userId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('user_roles ur')
                  ->join('roles r', 'r.id = ur.role_id')
                  ->where('ur.user_id', $userId)
                  ->where('ur.is_active', true)
                  ->where('r.is_active', true)
                  ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                  ->select('r.id, r.name, r.display_name, r.level, ur.assigned_at, ur.expires_at')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('user_roles ur')
                  ->join('role_permissions rp', 'rp.role_id = ur.role_id')
                  ->join('permissions p', 'p.id = rp.permission_id')
                  ->where('ur.user_id', $userId)
                  ->where('ur.is_active', true)
                  ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                  ->where('p.is_active', true)
                  ->select('p.name')
                  ->distinct()
                  ->get()
                  ->getResultArray();
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, int $roleId, ?int $assignedBy = null, ?string $expiresAt = null): bool
    {
        $userRoleModel = new UserRoleModel();
        
        $data = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'assigned_by' => $assignedBy,
            'assigned_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'is_active' => true,
        ];

        return $userRoleModel->insert($data) !== false;
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId, int $roleId): bool
    {
        $userRoleModel = new UserRoleModel();
        
        return $userRoleModel->where('user_id', $userId)
                            ->where('role_id', $roleId)
                            ->delete();
    }

    /**
     * Check if user has role
     */
    public function hasRole(int $userId, string $roleName): bool
    {
        $db = \Config\Database::connect();
        
        $result = $db->table('user_roles ur')
                     ->join('roles r', 'r.id = ur.role_id')
                     ->where('ur.user_id', $userId)
                     ->where('r.name', $roleName)
                     ->where('ur.is_active', true)
                     ->where('r.is_active', true)
                     ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                     ->countAllResults();

        return $result > 0;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(int $userId, string $permissionName): bool
    {
        $db = \Config\Database::connect();
        
        $result = $db->table('user_roles ur')
                     ->join('role_permissions rp', 'rp.role_id = ur.role_id')
                     ->join('permissions p', 'p.id = rp.permission_id')
                     ->where('ur.user_id', $userId)
                     ->where('p.name', $permissionName)
                     ->where('ur.is_active', true)
                     ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                     ->where('p.is_active', true)
                     ->countAllResults();

        return $result > 0;
    }

    /**
     * Update login information
     */
    public function updateLoginInfo(int $userId, string $ipAddress): bool
    {
        return $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ipAddress,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedAttempts(int $userId): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $attempts = $user['failed_login_attempts'] + 1;
        $data = ['failed_login_attempts' => $attempts];

        // Lock account after 5 failed attempts
        if ($attempts >= 5) {
            $data['locked_until'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
        }

        return $this->update($userId, $data);
    }

    /**
     * Check if user is locked
     */
    public function isLocked(int $userId): bool
    {
        $user = $this->find($userId);
        
        if (!$user || !$user['locked_until']) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(int $userId): ?string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $success = $this->update($userId, [
            'password_reset_token' => $token,
            'password_reset_expires' => $expires,
        ]);

        return $success ? $token : null;
    }

    /**
     * Verify password reset token
     */
    public function verifyPasswordResetToken(string $token): ?array
    {
        $user = $this->where('password_reset_token', $token)
                     ->where('password_reset_expires >', date('Y-m-d H:i:s'))
                     ->first();

        return $user;
    }

    /**
     * Reset password using token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->verifyPasswordResetToken($token);
        
        if (!$user) {
            return false;
        }

        return $this->update($user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'password_reset_token' => null,
            'password_reset_expires' => null,
        ]);
    }

    /**
     * Get users with pagination and filters
     */
    public function getUsers(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->builder();

        // Apply filters
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $builder->join('user_roles ur', 'ur.user_id = users.id')
                    ->join('roles r', 'r.id = ur.role_id')
                    ->where('r.name', $filters['role'])
                    ->where('ur.is_active', true);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('username', $filters['search'])
                    ->orLike('email', $filters['search'])
                    ->orLike('first_name', $filters['search'])
                    ->orLike('last_name', $filters['search'])
                    ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $users = $builder->orderBy('created_at', 'DESC')
                        ->limit($perPage, ($page - 1) * $perPage)
                        ->get()
                        ->getResultArray();

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }
}