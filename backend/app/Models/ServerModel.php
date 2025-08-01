<?php

namespace App\Models;

use CodeIgniter\Model;

class ServerModel extends Model
{
    protected $table = 'servers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'owner_id', 'server_code', 'server_name', 'game_type', 'version', 'description',
        'website_url', 'discord_url', 'server_ip', 'server_port', 'logo_image',
        'background_image', 'banner_images', 'max_players', 'online_players',
        'status', 'approval_date', 'approved_by', 'rejection_reason', 'tags',
        'features', 'social_links', 'stats', 'metadata', 'is_featured',
        'featured_until', 'last_ping_at', 'ping_status', 'sort_order'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'owner_id' => 'required|integer|is_not_unique[users.id]',
        'server_code' => 'required|alpha_numeric_punct|min_length[3]|max_length[20]|is_unique[servers.server_code,id,{id}]',
        'server_name' => 'required|min_length[3]|max_length[100]',
        'game_type' => 'required|alpha_numeric_punct|max_length[50]',
        'version' => 'permit_empty|max_length[50]',
        'website_url' => 'permit_empty|valid_url|max_length[255]',
        'discord_url' => 'permit_empty|valid_url|max_length[255]',
        'server_ip' => 'permit_empty|max_length[100]',
        'server_port' => 'permit_empty|integer|greater_than[0]|less_than[65536]',
        'max_players' => 'permit_empty|integer|greater_than[0]',
        'status' => 'permit_empty|in_list[pending,approved,rejected,suspended,inactive]',
    ];

    protected $validationMessages = [
        'server_code' => [
            'required' => 'Server code is required',
            'is_unique' => 'Server code already exists',
            'min_length' => 'Server code must be at least 3 characters',
            'max_length' => 'Server code cannot exceed 20 characters',
        ],
        'server_name' => [
            'required' => 'Server name is required',
            'min_length' => 'Server name must be at least 3 characters',
        ],
        'game_type' => [
            'required' => 'Game type is required',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generateServerCode'];

    /**
     * Generate unique server code if not provided
     */
    protected function generateServerCode(array $data)
    {
        if (!isset($data['data']['server_code']) || empty($data['data']['server_code'])) {
            $data['data']['server_code'] = $this->generateUniqueCode();
        }

        return $data;
    }

    /**
     * Generate unique server code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = 'SRV' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while ($this->where('server_code', $code)->countAllResults() > 0);

        return $code;
    }

    /**
     * Get server with owner details
     */
    public function getServerWithOwner(int $serverId): ?array
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' s')
                  ->join('users u', 'u.id = s.owner_id')
                  ->where('s.id', $serverId)
                  ->where('s.deleted_at', null)
                  ->select('
                      s.*,
                      u.username as owner_username,
                      u.email as owner_email,
                      u.first_name as owner_first_name,
                      u.last_name as owner_last_name
                  ')
                  ->get()
                  ->getRowArray();
    }

    /**
     * Get servers with pagination and filters
     */
    public function getServers(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->builder()->select('s.*, u.username as owner_username')
                        ->join('users u', 'u.id = s.owner_id', 'left')
                        ->where('s.deleted_at', null);

        // Apply filters
        if (!empty($filters['status'])) {
            $builder->where('s.status', $filters['status']);
        }

        if (!empty($filters['game_type'])) {
            $builder->where('s.game_type', $filters['game_type']);
        }

        if (!empty($filters['owner_id'])) {
            $builder->where('s.owner_id', $filters['owner_id']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('s.server_name', $filters['search'])
                    ->orLike('s.server_code', $filters['search'])
                    ->orLike('s.description', $filters['search'])
                    ->orLike('u.username', $filters['search'])
                    ->groupEnd();
        }

        if (isset($filters['is_featured'])) {
            $builder->where('s.is_featured', $filters['is_featured']);
        }

        if (!empty($filters['ping_status'])) {
            $builder->where('s.ping_status', $filters['ping_status']);
        }

        $total = $builder->countAllResults(false);
        
        $servers = $builder->orderBy('s.sort_order', 'ASC')
                          ->orderBy('s.created_at', 'DESC')
                          ->limit($perPage, ($page - 1) * $perPage)
                          ->get()
                          ->getResultArray();

        return [
            'servers' => $servers,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get user's servers
     */
    public function getUserServers(int $userId, ?string $status = null): array
    {
        $builder = $this->where('owner_id', $userId)
                        ->where('deleted_at', null);

        if ($status) {
            $builder->where('status', $status);
        }

        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Approve server
     */
    public function approveServer(int $serverId, int $approvedBy, ?string $note = null): bool
    {
        $data = [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approval_date' => date('Y-m-d H:i:s'),
            'rejection_reason' => null, // Clear any previous rejection reason
        ];

        if ($note) {
            $metadata = $this->find($serverId)['metadata'] ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            }
            $metadata['approval_note'] = $note;
            $data['metadata'] = json_encode($metadata);
        }

        return $this->update($serverId, $data);
    }

    /**
     * Reject server
     */
    public function rejectServer(int $serverId, int $rejectedBy, string $reason): bool
    {
        $data = [
            'status' => 'rejected',
            'approved_by' => $rejectedBy, // Track who rejected it
            'rejection_reason' => $reason,
            'approval_date' => null,
        ];

        return $this->update($serverId, $data);
    }

    /**
     * Suspend server
     */
    public function suspendServer(int $serverId, int $suspendedBy, string $reason): bool
    {
        $data = [
            'status' => 'suspended',
        ];

        // Add suspension info to metadata
        $server = $this->find($serverId);
        $metadata = $server['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }
        
        $metadata['suspension'] = [
            'suspended_by' => $suspendedBy,
            'suspended_at' => date('Y-m-d H:i:s'),
            'reason' => $reason,
        ];
        
        $data['metadata'] = json_encode($metadata);

        return $this->update($serverId, $data);
    }

    /**
     * Update server status
     */
    public function updateStatus(int $serverId, string $status): bool
    {
        return $this->update($serverId, ['status' => $status]);
    }

    /**
     * Update server ping status
     */
    public function updatePingStatus(int $serverId, string $pingStatus, ?int $onlinePlayers = null): bool
    {
        $data = [
            'ping_status' => $pingStatus,
            'last_ping_at' => date('Y-m-d H:i:s'),
        ];

        if ($onlinePlayers !== null) {
            $data['online_players'] = $onlinePlayers;
        }

        return $this->update($serverId, $data);
    }

    /**
     * Set server as featured
     */
    public function setFeatured(int $serverId, bool $featured = true, ?string $featuredUntil = null): bool
    {
        $data = [
            'is_featured' => $featured,
            'featured_until' => $featured ? $featuredUntil : null,
        ];

        return $this->update($serverId, $data);
    }

    /**
     * Get featured servers
     */
    public function getFeaturedServers(int $limit = 10): array
    {
        return $this->where('is_featured', true)
                    ->where('status', 'approved')
                    ->where('deleted_at', null)
                    ->where('(featured_until IS NULL OR featured_until > NOW())')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get servers by game type
     */
    public function getServersByGameType(string $gameType, int $limit = 20): array
    {
        return $this->where('game_type', $gameType)
                    ->where('status', 'approved')
                    ->where('deleted_at', null)
                    ->orderBy('is_featured', 'DESC')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('online_players', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Search servers
     */
    public function searchServers(string $query, array $filters = [], int $limit = 20): array
    {
        $builder = $this->where('status', 'approved')
                        ->where('deleted_at', null);

        // Apply search
        if (!empty($query)) {
            $builder->groupStart()
                    ->like('server_name', $query)
                    ->orLike('description', $query)
                    ->orLike('tags', $query)
                    ->groupEnd();
        }

        // Apply filters
        if (!empty($filters['game_type'])) {
            $builder->where('game_type', $filters['game_type']);
        }

        if (!empty($filters['min_players'])) {
            $builder->where('max_players >=', $filters['min_players']);
        }

        if (!empty($filters['features'])) {
            foreach ($filters['features'] as $feature) {
                $builder->like('features', $feature);
            }
        }

        return $builder->orderBy('is_featured', 'DESC')
                      ->orderBy('online_players', 'DESC')
                      ->orderBy('server_name', 'ASC')
                      ->limit($limit)
                      ->findAll();
    }

    /**
     * Get server statistics
     */
    public function getServerStats(): array
    {
        $db = \Config\Database::connect();
        
        // Total servers by status
        $statusStats = $db->table($this->table)
                          ->where('deleted_at', null)
                          ->groupBy('status')
                          ->select('status, COUNT(*) as count')
                          ->get()
                          ->getResultArray();

        // Servers by game type
        $gameTypeStats = $db->table($this->table)
                            ->where('status', 'approved')
                            ->where('deleted_at', null)
                            ->groupBy('game_type')
                            ->select('game_type, COUNT(*) as count')
                            ->orderBy('count', 'DESC')
                            ->get()
                            ->getResultArray();

        // Recent servers (last 30 days)
        $recentServers = $this->where('created_at >', date('Y-m-d H:i:s', strtotime('-30 days')))
                              ->where('deleted_at', null)
                              ->countAllResults();

        // Featured servers count
        $featuredCount = $this->where('is_featured', true)
                              ->where('status', 'approved')
                              ->where('deleted_at', null)
                              ->where('(featured_until IS NULL OR featured_until > NOW())')
                              ->countAllResults();

        // Online servers
        $onlineServers = $this->where('ping_status', 'online')
                              ->where('status', 'approved')
                              ->where('deleted_at', null)
                              ->countAllResults();

        return [
            'status_stats' => $statusStats,
            'game_type_stats' => $gameTypeStats,
            'recent_servers' => $recentServers,
            'featured_count' => $featuredCount,
            'online_servers' => $onlineServers,
        ];
    }

    /**
     * Get pending approvals
     */
    public function getPendingApprovals(int $limit = 50): array
    {
        return $this->select('s.*, u.username as owner_username, u.email as owner_email')
                    ->join('users u', 'u.id = s.owner_id', 'left')
                    ->where('s.status', 'pending')
                    ->where('s.deleted_at', null)
                    ->orderBy('s.created_at', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Update server sort order
     */
    public function updateSortOrder(int $serverId, int $sortOrder): bool
    {
        return $this->update($serverId, ['sort_order' => $sortOrder]);
    }

    /**
     * Clean expired featured servers
     */
    public function cleanExpiredFeatured(): int
    {
        return $this->where('is_featured', true)
                    ->where('featured_until <', date('Y-m-d H:i:s'))
                    ->set(['is_featured' => false, 'featured_until' => null])
                    ->update();
    }

    /**
     * Get servers needing ping check
     */
    public function getServersForPingCheck(int $minutesSinceLastPing = 5): array
    {
        return $this->where('status', 'approved')
                    ->where('deleted_at', null)
                    ->where('server_ip IS NOT NULL')
                    ->where('server_port IS NOT NULL')
                    ->groupStart()
                        ->where('last_ping_at IS NULL')
                        ->orWhere('last_ping_at <', date('Y-m-d H:i:s', strtotime("-{$minutesSinceLastPing} minutes")))
                    ->groupEnd()
                    ->select('id, server_code, server_name, server_ip, server_port, last_ping_at')
                    ->findAll();
    }

    /**
     * Check if user can manage server
     */
    public function canUserManage(int $serverId, int $userId): bool
    {
        $server = $this->find($serverId);
        
        if (!$server) {
            return false;
        }

        // Owner can always manage
        if ($server['owner_id'] == $userId) {
            return true;
        }

        // Check if user has admin role
        $userModel = new UserModel();
        return $userModel->hasRole($userId, 'admin') || $userModel->hasRole($userId, 'super_admin');
    }

    /**
     * Get server activity log
     */
    public function getServerActivityLog(int $serverId, int $limit = 50): array
    {
        // This would be implemented when activity logging is added
        // For now, return empty array
        return [];
    }
}