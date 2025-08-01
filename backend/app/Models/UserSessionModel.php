<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSessionModel extends Model
{
    protected $table = 'user_sessions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'user_id', 'session_token', 'refresh_token', 'device_info', 'ip_address',
        'location_info', 'is_active', 'expires_at', 'refresh_expires_at', 'last_activity_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer|is_not_unique[users.id]',
        'session_token' => 'required|min_length[32]|max_length[255]|is_unique[user_sessions.session_token,id,{id}]',
        'refresh_token' => 'required|min_length[32]|max_length[255]|is_unique[user_sessions.refresh_token,id,{id}]',
        'ip_address' => 'required|valid_ip',
        'expires_at' => 'required|valid_date',
        'refresh_expires_at' => 'required|valid_date',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID is required',
            'is_not_unique' => 'User does not exist',
        ],
        'session_token' => [
            'required' => 'Session token is required',
            'is_unique' => 'Session token already exists',
        ],
        'refresh_token' => [
            'required' => 'Refresh token is required',
            'is_unique' => 'Refresh token already exists',
        ],
        'ip_address' => [
            'required' => 'IP address is required',
            'valid_ip' => 'Invalid IP address format',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setLastActivity'];
    protected $beforeUpdate = ['setLastActivity'];

    /**
     * Set last activity timestamp
     */
    protected function setLastActivity(array $data)
    {
        if (!isset($data['data']['last_activity_at'])) {
            $data['data']['last_activity_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Get active session by token
     */
    public function getActiveSessionByToken(string $sessionToken): ?array
    {
        return $this->where('session_token', $sessionToken)
                    ->where('is_active', true)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * Get active session by refresh token
     */
    public function getActiveSessionByRefreshToken(string $refreshToken): ?array
    {
        return $this->where('refresh_token', $refreshToken)
                    ->where('is_active', true)
                    ->where('refresh_expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * Get user active sessions
     */
    public function getUserActiveSessions(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_active', true)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->orderBy('last_activity_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get user sessions with details
     */
    public function getUserSessionsWithDetails(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('last_activity_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Deactivate session
     */
    public function deactivateSession(string $sessionToken): bool
    {
        return $this->where('session_token', $sessionToken)
                    ->set(['is_active' => false])
                    ->update();
    }

    /**
     * Deactivate all user sessions
     */
    public function deactivateAllUserSessions(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->set(['is_active' => false])
                    ->update();
    }

    /**
     * Deactivate all user sessions except current
     */
    public function deactivateOtherUserSessions(int $userId, string $currentSessionToken): bool
    {
        return $this->where('user_id', $userId)
                    ->where('session_token !=', $currentSessionToken)
                    ->set(['is_active' => false])
                    ->update();
    }

    /**
     * Update session activity
     */
    public function updateActivity(string $sessionToken): bool
    {
        return $this->where('session_token', $sessionToken)
                    ->set(['last_activity_at' => date('Y-m-d H:i:s')])
                    ->update();
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                    ->orWhere('refresh_expires_at <', date('Y-m-d H:i:s'))
                    ->delete();
    }

    /**
     * Clean inactive sessions (older than specified days)
     */
    public function cleanInactiveSessions(int $days = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->where('last_activity_at <', $cutoffDate)
                    ->where('is_active', false)
                    ->delete();
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): array
    {
        $db = \Config\Database::connect();
        
        // Total active sessions
        $activeSessions = $this->where('is_active', true)
                               ->where('expires_at >', date('Y-m-d H:i:s'))
                               ->countAllResults();

        // Sessions by device type (from device_info)
        $deviceStats = $db->table($this->table)
                          ->where('is_active', true)
                          ->where('expires_at >', date('Y-m-d H:i:s'))
                          ->select('device_info')
                          ->get()
                          ->getResultArray();

        $devices = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'unknown' => 0];
        foreach ($deviceStats as $session) {
            $deviceInfo = json_decode($session['device_info'], true);
            $platform = strtolower($deviceInfo['platform'] ?? 'unknown');
            
            if (in_array($platform, ['windows', 'mac', 'linux'])) {
                $devices['desktop']++;
            } elseif (in_array($platform, ['android', 'ios'])) {
                $devices['mobile']++;
            } elseif (strpos($platform, 'tablet') !== false) {
                $devices['tablet']++;
            } else {
                $devices['unknown']++;
            }
        }

        // Recent sessions (last 24 hours)
        $recentSessions = $this->where('created_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                               ->countAllResults();

        // Sessions by hour (last 24 hours)
        $hourlyStats = $db->table($this->table)
                          ->where('created_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                          ->select('HOUR(created_at) as hour, COUNT(*) as count')
                          ->groupBy('HOUR(created_at)')
                          ->orderBy('hour')
                          ->get()
                          ->getResultArray();

        // Average session duration (for completed sessions)
        $avgDuration = $db->table($this->table)
                          ->where('is_active', false)
                          ->where('last_activity_at IS NOT NULL')
                          ->select('AVG(TIMESTAMPDIFF(MINUTE, created_at, last_activity_at)) as avg_minutes')
                          ->get()
                          ->getRowArray()['avg_minutes'] ?? 0;

        return [
            'active_sessions' => $activeSessions,
            'device_stats' => $devices,
            'recent_sessions' => $recentSessions,
            'hourly_stats' => $hourlyStats,
            'avg_duration_minutes' => round($avgDuration, 2),
        ];
    }

    /**
     * Get concurrent sessions count
     */
    public function getConcurrentSessionsCount(): int
    {
        return $this->where('is_active', true)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->where('last_activity_at >', date('Y-m-d H:i:s', strtotime('-15 minutes')))
                    ->countAllResults();
    }

    /**
     * Get sessions by IP address
     */
    public function getSessionsByIP(string $ipAddress, int $limit = 50): array
    {
        return $this->where('ip_address', $ipAddress)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Check for suspicious activity (multiple sessions from different IPs)
     */
    public function getSuspiciousActivity(int $hoursBack = 24): array
    {
        $db = \Config\Database::connect();
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hoursBack} hours"));
        
        return $db->table($this->table . ' s')
                  ->join('users u', 'u.id = s.user_id')
                  ->where('s.created_at >', $cutoffTime)
                  ->groupBy('s.user_id')
                  ->having('COUNT(DISTINCT s.ip_address) > 3') // More than 3 different IPs
                  ->select('
                      s.user_id,
                      u.username,
                      u.email,
                      COUNT(DISTINCT s.ip_address) as ip_count,
                      COUNT(*) as session_count,
                      GROUP_CONCAT(DISTINCT s.ip_address) as ip_addresses
                  ')
                  ->orderBy('ip_count', 'DESC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get sessions needing cleanup
     */
    public function getSessionsNeedingCleanup(): array
    {
        return [
            'expired' => $this->where('expires_at <', date('Y-m-d H:i:s'))
                              ->countAllResults(),
            'refresh_expired' => $this->where('refresh_expires_at <', date('Y-m-d H:i:s'))
                                      ->countAllResults(),
            'inactive_30_days' => $this->where('last_activity_at <', date('Y-m-d H:i:s', strtotime('-30 days')))
                                       ->where('is_active', false)
                                       ->countAllResults(),
        ];
    }

    /**
     * Force logout user from all devices
     */
    public function forceLogoutUser(int $userId, ?string $reason = null): bool
    {
        $data = ['is_active' => false];
        
        if ($reason) {
            // Add logout reason to device_info if needed
            $sessions = $this->where('user_id', $userId)
                             ->where('is_active', true)
                             ->findAll();
            
            foreach ($sessions as $session) {
                $deviceInfo = json_decode($session['device_info'], true) ?? [];
                $deviceInfo['force_logout_reason'] = $reason;
                $deviceInfo['force_logout_at'] = date('Y-m-d H:i:s');
                
                $this->update($session['id'], [
                    'is_active' => false,
                    'device_info' => json_encode($deviceInfo)
                ]);
            }
            
            return true;
        }

        return $this->where('user_id', $userId)
                    ->set($data)
                    ->update();
    }

    /**
     * Get user's current active session count
     */
    public function getUserActiveSessionCount(int $userId): int
    {
        return $this->where('user_id', $userId)
                    ->where('is_active', true)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->countAllResults();
    }

    /**
     * Extend session expiration
     */
    public function extendSession(string $sessionToken, int $extensionSeconds = 3600): bool
    {
        $session = $this->getActiveSessionByToken($sessionToken);
        
        if (!$session) {
            return false;
        }

        $newExpiration = date('Y-m-d H:i:s', strtotime($session['expires_at']) + $extensionSeconds);
        
        return $this->update($session['id'], [
            'expires_at' => $newExpiration,
            'last_activity_at' => date('Y-m-d H:i:s')
        ]);
    }
}