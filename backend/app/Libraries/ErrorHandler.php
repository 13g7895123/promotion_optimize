<?php

namespace App\Libraries;

use CodeIgniter\Log\Logger;
use CodeIgniter\Cache\CacheInterface;

class ErrorHandler
{
    private Logger $logger;
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->logger = service('logger');
        $this->cache = service('cache');
        
        $this->config = [
            'enable_detailed_logging' => env('CI_ENVIRONMENT') === 'development',
            'enable_email_notifications' => env('ERROR_EMAIL_NOTIFICATIONS', false),
            'notification_email' => env('ERROR_NOTIFICATION_EMAIL', ''),
            'max_error_cache_entries' => 1000,
            'error_cache_ttl' => 3600,
            'critical_error_threshold' => 5, // errors per minute to trigger alert
        ];
    }

    /**
     * Handle application errors with context
     */
    public function handleError(\Throwable $error, array $context = []): string
    {
        $errorId = $this->generateErrorId();
        
        $errorData = [
            'id' => $errorId,
            'type' => get_class($error),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'code' => $error->getCode(),
            'trace' => $this->formatStackTrace($error->getTrace()),
            'context' => $context,
            'request_data' => $this->gatherRequestData(),
            'system_info' => $this->gatherSystemInfo(),
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => $this->determineSeverity($error),
        ];

        // Log error
        $this->logError($errorData);
        
        // Cache error for dashboard/reporting
        $this->cacheError($errorData);
        
        // Check if critical error threshold is exceeded
        $this->checkCriticalThreshold($errorData);
        
        // Send notifications if configured
        if ($errorData['severity'] >= 8) { // Critical errors
            $this->sendNotification($errorData);
        }

        return $errorId;
    }

    /**
     * Handle API validation errors
     */
    public function handleValidationError(array $validationErrors, array $context = []): array
    {
        $errorData = [
            'type' => 'validation_error',
            'errors' => $validationErrors,
            'context' => $context,
            'request_data' => $this->gatherRequestData(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->logger->warning('Validation Error', $errorData);
        
        return [
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validationErrors,
            'code' => 'VALIDATION_ERROR'
        ];
    }

    /**
     * Handle database errors
     */
    public function handleDatabaseError(\Throwable $error, string $query = '', array $bindings = []): string
    {
        $errorId = $this->generateErrorId();
        
        $errorData = [
            'id' => $errorId,
            'type' => 'database_error',
            'message' => $error->getMessage(),
            'query' => $query,
            'bindings' => $this->sanitizeBindings($bindings),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $this->formatStackTrace($error->getTrace()),
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => 7, // High severity for DB errors
        ];

        $this->logError($errorData, 'database');
        $this->cacheError($errorData);

        return $errorId;
    }

    /**
     * Handle security incidents
     */
    public function handleSecurityIncident(string $type, array $details, string $severity = 'high'): string
    {
        $incidentId = $this->generateErrorId();
        
        $incidentData = [
            'id' => $incidentId,
            'type' => 'security_incident',
            'incident_type' => $type,
            'details' => $details,
            'request_data' => $this->gatherRequestData(),
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => $this->mapSeverityToNumber($severity),
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        $this->logger->critical('Security Incident', $incidentData);
        $this->cacheError($incidentData);
        
        // Always send notifications for security incidents
        $this->sendNotification($incidentData);

        return $incidentId;
    }

    /**
     * Generate unique error ID
     */
    private function generateErrorId(): string
    {
        return 'ERR_' . date('Ymd_His') . '_' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Format stack trace for logging
     */
    private function formatStackTrace(array $trace): array
    {
        $formatted = [];
        
        foreach ($trace as $index => $frame) {
            $formatted[] = [
                'index' => $index,
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
            
            // Limit trace depth in production
            if (!$this->config['enable_detailed_logging'] && $index >= 10) {
                $formatted[] = ['truncated' => '... (trace truncated)'];
                break;
            }
        }
        
        return $formatted;
    }

    /**
     * Gather request data for error context
     */
    private function gatherRequestData(): array
    {
        $request = service('request');
        
        return [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
            'body' => $this->sanitizeRequestBody($request),
            'ip' => $this->getClientIP(),
            'user_agent' => $request->getUserAgent(),
            'session_id' => session_id(),
        ];
    }

    /**
     * Gather system information
     */
    private function gatherSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'ci_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'load_average' => sys_getloadavg(),
            'disk_free_space' => disk_free_space('.'),
        ];
    }

    /**
     * Determine error severity (1-10 scale)
     */
    private function determineSeverity(\Throwable $error): int
    {
        $type = get_class($error);
        
        $severityMap = [
            'Error' => 8,
            'ParseError' => 9,
            'TypeError' => 7,
            'ArgumentCountError' => 6,
            'ValueError' => 6,
            'UnhandledMatchError' => 7,
            'Exception' => 5,
            'RuntimeException' => 6,
            'LogicException' => 7,
            'InvalidArgumentException' => 5,
            'OutOfBoundsException' => 5,
            'PDOException' => 8,
        ];

        // Get base class name
        $baseType = substr($type, strrpos($type, '\\') + 1);
        
        return $severityMap[$baseType] ?? 5;
    }

    /**
     * Map string severity to number
     */
    private function mapSeverityToNumber(string $severity): int
    {
        $map = [
            'low' => 3,
            'medium' => 5,
            'high' => 7,
            'critical' => 9,
        ];
        
        return $map[strtolower($severity)] ?? 5;
    }

    /**
     * Log error with appropriate level
     */
    private function logError(array $errorData, string $category = 'general'): void
    {
        $severity = $errorData['severity'] ?? 5;
        $message = "Error ID: {$errorData['id']} - {$errorData['message'] ?? 'Unknown error'}";
        
        if ($severity >= 8) {
            $this->logger->critical($message, $errorData);
        } elseif ($severity >= 6) {
            $this->logger->error($message, $errorData);
        } elseif ($severity >= 4) {
            $this->logger->warning($message, $errorData);
        } else {
            $this->logger->info($message, $errorData);
        }
    }

    /**
     * Cache error for dashboard/reporting
     */
    private function cacheError(array $errorData): void
    {
        $cacheKey = 'recent_errors';
        $errors = $this->cache->get($cacheKey) ?? [];
        
        // Add new error to the beginning
        array_unshift($errors, $errorData);
        
        // Limit cache size
        if (count($errors) > $this->config['max_error_cache_entries']) {
            $errors = array_slice($errors, 0, $this->config['max_error_cache_entries']);
        }
        
        $this->cache->save($cacheKey, $errors, $this->config['error_cache_ttl']);
    }

    /**
     * Check if critical error threshold is exceeded
     */
    private function checkCriticalThreshold(array $errorData): void
    {
        if ($errorData['severity'] < 8) return;
        
        $minute = date('Y-m-d-H-i');
        $key = "critical_errors_{$minute}";
        
        $count = $this->cache->get($key) ?? 0;
        $count++;
        
        $this->cache->save($key, $count, 60);
        
        if ($count >= $this->config['critical_error_threshold']) {
            $this->handleCriticalThresholdExceeded($count, $minute);
        }
    }

    /**
     * Handle critical error threshold exceeded
     */
    private function handleCriticalThresholdExceeded(int $count, string $minute): void
    {
        $alertData = [
            'type' => 'critical_threshold_exceeded',
            'error_count' => $count,
            'time_window' => $minute,
            'threshold' => $this->config['critical_error_threshold'],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->logger->critical('Critical Error Threshold Exceeded', $alertData);
        $this->sendNotification($alertData);
    }

    /**
     * Send error notification
     */
    private function sendNotification(array $errorData): void
    {
        if (!$this->config['enable_email_notifications'] || empty($this->config['notification_email'])) {
            return;
        }

        try {
            $email = service('email');
            $email->setTo($this->config['notification_email']);
            $email->setSubject('Critical Error Alert - ' . ($errorData['id'] ?? 'Unknown'));
            $email->setMessage($this->formatNotificationMessage($errorData));
            $email->send();
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send error notification: ' . $e->getMessage());
        }
    }

    /**
     * Format notification message
     */
    private function formatNotificationMessage(array $errorData): string
    {
        $message = "Critical Error Alert\n\n";
        $message .= "Error ID: " . ($errorData['id'] ?? 'Unknown') . "\n";
        $message .= "Type: " . ($errorData['type'] ?? 'Unknown') . "\n";
        $message .= "Message: " . ($errorData['message'] ?? 'No message') . "\n";
        $message .= "Severity: " . ($errorData['severity'] ?? 'Unknown') . "\n";
        $message .= "Timestamp: " . ($errorData['timestamp'] ?? 'Unknown') . "\n\n";
        
        if (isset($errorData['file']) && isset($errorData['line'])) {
            $message .= "Location: {$errorData['file']}:{$errorData['line']}\n\n";
        }
        
        if (isset($errorData['request_data']['uri'])) {
            $message .= "Request URI: " . $errorData['request_data']['uri'] . "\n";
        }
        
        if (isset($errorData['request_data']['ip'])) {
            $message .= "Client IP: " . $errorData['request_data']['ip'] . "\n";
        }
        
        return $message;
    }

    /**
     * Sanitize request headers for logging
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), $sensitiveHeaders)) {
                $sanitized[$name] = '[REDACTED]';
            } else {
                $sanitized[$name] = is_array($value) ? $value : [$value];
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize request body for logging
     */
    private function sanitizeRequestBody($request): string
    {
        $rawBody = $request->getBody();
        
        if (empty($rawBody)) {
            return '';
        }

        // Try to parse as JSON
        $jsonData = json_decode($rawBody, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($this->sanitizeSensitiveData($jsonData));
        }
        
        // For non-JSON data, just return first 1000 characters
        return substr($rawBody, 0, 1000);
    }

    /**
     * Sanitize sensitive data in arrays
     */
    private function sanitizeSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'auth', 'credential'];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeSensitiveData($value);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $data[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * Sanitize database bindings
     */
    private function sanitizeBindings(array $bindings): array
    {
        return $this->sanitizeSensitiveData($bindings);
    }

    /**
     * Get client IP address
     */
    private function getClientIP(): string
    {
        $request = service('request');
        return $request->getIPAddress();
    }

    /**
     * Get recent errors from cache
     */
    public function getRecentErrors(int $limit = 50): array
    {
        $errors = $this->cache->get('recent_errors') ?? [];
        return array_slice($errors, 0, $limit);
    }

    /**
     * Get error statistics
     */
    public function getErrorStats(): array
    {
        $errors = $this->getRecentErrors(1000);
        
        $stats = [
            'total_errors' => count($errors),
            'by_severity' => [],
            'by_type' => [],
            'by_hour' => [],
            'most_common' => [],
        ];

        foreach ($errors as $error) {
            // Count by severity
            $severity = $error['severity'] ?? 5;
            $stats['by_severity'][$severity] = ($stats['by_severity'][$severity] ?? 0) + 1;
            
            // Count by type
            $type = $error['type'] ?? 'unknown';
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            
            // Count by hour
            $hour = substr($error['timestamp'] ?? '', 0, 13); // YYYY-MM-DD HH
            $stats['by_hour'][$hour] = ($stats['by_hour'][$hour] ?? 0) + 1;
        }

        // Sort most common errors
        arsort($stats['by_type']);
        $stats['most_common'] = array_slice($stats['by_type'], 0, 10, true);

        return $stats;
    }

    /**
     * Clear error cache
     */
    public function clearErrorCache(): bool
    {
        return $this->cache->delete('recent_errors');
    }
}