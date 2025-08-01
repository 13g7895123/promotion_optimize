<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table = 'user_roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'user_id', 'role_id', 'assigned_by', 'assigned_at', 'expires_at', 'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer|is_not_unique[users.id]',
        'role_id' => 'required|integer|is_not_unique[roles.id]',
        'assigned_by' => 'permit_empty|integer|is_not_unique[users.id]',
        'expires_at' => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID is required',
            'is_not_unique' => 'User does not exist',
        ],
        'role_id' => [
            'required' => 'Role ID is required',
            'is_not_unique' => 'Role does not exist',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setAssignedAt'];

    /**
     * Set assigned_at timestamp
     */
    protected function setAssignedAt(array $data)
    {
        if (!isset($data['data']['assigned_at'])) {
            $data['data']['assigned_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Get user role assignments with details
     */
    public function getUserRoleAssignments(int $userId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' ur')
                  ->join('roles r', 'r.id = ur.role_id')
                  ->join('users assigner', 'assigner.id = ur.assigned_by', 'left')
                  ->where('ur.user_id', $userId)
                  ->select('
                      ur.id,
                      ur.user_id,
                      ur.role_id,
                      ur.assigned_at,
                      ur.expires_at,
                      ur.is_active,
                      r.name as role_name,
                      r.display_name as role_display_name,
                      r.level as role_level,
                      assigner.username as assigned_by_username
                  ')
                  ->orderBy('ur.assigned_at', 'DESC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get role user assignments with details
     */
    public function getRoleUserAssignments(int $roleId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' ur')
                  ->join('users u', 'u.id = ur.user_id')
                  ->join('users assigner', 'assigner.id = ur.assigned_by', 'left')
                  ->where('ur.role_id', $roleId)
                  ->where('u.deleted_at', null)
                  ->select('
                      ur.id,
                      ur.user_id,
                      ur.role_id,
                      ur.assigned_at,
                      ur.expires_at,
                      ur.is_active,
                      u.username,
                      u.email,
                      u.first_name,
                      u.last_name,
                      u.status as user_status,
                      assigner.username as assigned_by_username
                  ')
                  ->orderBy('ur.assigned_at', 'DESC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get active role assignments
     */
    public function getActiveAssignments(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_active', true)
                    ->where('(expires_at IS NULL OR expires_at > NOW())')
                    ->findAll();
    }

    /**
     * Check if user has active role assignment
     */
    public function hasActiveRole(int $userId, int $roleId): bool
    {
        $result = $this->where('user_id', $userId)
                       ->where('role_id', $roleId)
                       ->where('is_active', true)
                       ->where('(expires_at IS NULL OR expires_at > NOW())')
                       ->countAllResults();

        return $result > 0;
    }

    /**
     * Deactivate role assignment
     */
    public function deactivateAssignment(int $userId, int $roleId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('role_id', $roleId)
                    ->set(['is_active' => false])
                    ->update();
    }

    /**
     * Extend role assignment expiration
     */
    public function extendAssignment(int $assignmentId, string $newExpirationDate): bool
    {
        return $this->update($assignmentId, ['expires_at' => $newExpirationDate]);
    }

    /**
     * Get expiring assignments
     */
    public function getExpiringAssignments(int $daysBeforeExpiry = 7): array
    {
        $db = \Config\Database::connect();
        $expiryDate = date('Y-m-d H:i:s', strtotime("+{$daysBeforeExpiry} days"));
        
        return $db->table($this->table . ' ur')
                  ->join('users u', 'u.id = ur.user_id')
                  ->join('roles r', 'r.id = ur.role_id')
                  ->where('ur.is_active', true)
                  ->where('ur.expires_at IS NOT NULL')
                  ->where('ur.expires_at <=', $expiryDate)
                  ->where('ur.expires_at >', date('Y-m-d H:i:s'))
                  ->where('u.deleted_at', null)
                  ->select('
                      ur.id,
                      ur.user_id,
                      ur.role_id,
                      ur.expires_at,
                      u.username,
                      u.email,
                      u.first_name,
                      u.last_name,
                      r.name as role_name,
                      r.display_name as role_display_name
                  ')
                  ->orderBy('ur.expires_at', 'ASC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Clean expired assignments
     */
    public function cleanExpiredAssignments(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                    ->where('is_active', true)
                    ->set(['is_active' => false])
                    ->update();
    }

    /**
     * Get assignment history for user
     */
    public function getUserAssignmentHistory(int $userId, int $limit = 50): array
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' ur')
                  ->join('roles r', 'r.id = ur.role_id')
                  ->join('users assigner', 'assigner.id = ur.assigned_by', 'left')
                  ->where('ur.user_id', $userId)
                  ->select('
                      ur.id,
                      ur.assigned_at,
                      ur.expires_at,
                      ur.is_active,
                      ur.created_at,
                      ur.updated_at,
                      r.name as role_name,
                      r.display_name as role_display_name,
                      r.level as role_level,
                      assigner.username as assigned_by_username
                  ')
                  ->orderBy('ur.created_at', 'DESC')
                  ->limit($limit)
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get role assignment statistics
     */
    public function getAssignmentStats(): array
    {
        $db = \Config\Database::connect();
        
        // Total active assignments
        $activeAssignments = $this->where('is_active', true)
                                  ->where('(expires_at IS NULL OR expires_at > NOW())')
                                  ->countAllResults();

        // Expired but still active assignments (should be cleaned)
        $expiredActive = $this->where('is_active', true)
                              ->where('expires_at <', date('Y-m-d H:i:s'))
                              ->countAllResults();

        // Assignments by role
        $roleStats = $db->table($this->table . ' ur')
                        ->join('roles r', 'r.id = ur.role_id')
                        ->where('ur.is_active', true)
                        ->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())')
                        ->groupBy('ur.role_id')
                        ->select('ur.role_id, r.name as role_name, COUNT(*) as count')
                        ->get()
                        ->getResultArray();

        // Recent assignments (last 30 days)
        $recentAssignments = $this->where('assigned_at >', date('Y-m-d H:i:s', strtotime('-30 days')))
                                  ->countAllResults();

        return [
            'active_assignments' => $activeAssignments,
            'expired_active' => $expiredActive,
            'role_stats' => $roleStats,
            'recent_assignments' => $recentAssignments,
        ];
    }

    /**
     * Bulk assign roles to users
     */
    public function bulkAssignRoles(array $assignments): bool
    {
        $data = [];
        $now = date('Y-m-d H:i:s');

        foreach ($assignments as $assignment) {
            // Check if assignment already exists
            $existing = $this->where('user_id', $assignment['user_id'])
                             ->where('role_id', $assignment['role_id'])
                             ->where('is_active', true)
                             ->first();

            if (!$existing) {
                $data[] = [
                    'user_id' => $assignment['user_id'],
                    'role_id' => $assignment['role_id'],
                    'assigned_by' => $assignment['assigned_by'] ?? null,
                    'assigned_at' => $now,
                    'expires_at' => $assignment['expires_at'] ?? null,
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
     * Transfer role assignments from one user to another
     */
    public function transferAssignments(int $fromUserId, int $toUserId, ?int $transferredBy = null): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Get active assignments from source user
            $assignments = $this->where('user_id', $fromUserId)
                                ->where('is_active', true)
                                ->where('(expires_at IS NULL OR expires_at > NOW())')
                                ->findAll();

            foreach ($assignments as $assignment) {
                // Check if target user already has this role
                $existing = $this->where('user_id', $toUserId)
                                 ->where('role_id', $assignment['role_id'])
                                 ->where('is_active', true)
                                 ->first();

                if (!$existing) {
                    // Create new assignment for target user
                    $newData = [
                        'user_id' => $toUserId,
                        'role_id' => $assignment['role_id'],
                        'assigned_by' => $transferredBy,
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'expires_at' => $assignment['expires_at'],
                        'is_active' => true,
                    ];
                    $this->insert($newData);
                }

                // Deactivate source assignment
                $this->update($assignment['id'], ['is_active' => false]);
            }

            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {
            log_message('error', 'Error transferring role assignments: ' . $e->getMessage());
            return false;
        }
    }
}