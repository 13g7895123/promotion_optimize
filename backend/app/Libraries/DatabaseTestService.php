<?php

namespace App\Libraries;

use CodeIgniter\Database\Config;

class DatabaseTestService
{
    private array $supportedDrivers = ['MySQLi', 'Postgre', 'SQLite3'];
    private array $commonPorts = [
        'mysql' => 3306,
        'mysqli' => 3306, 
        'postgre' => 5432,
        'sqlite3' => null,
    ];

    /**
     * Test database connection with provided settings
     */
    public function testConnection(array $connectionSettings): array
    {
        try {
            $startTime = microtime(true);
            
            // Validate connection settings
            $validation = $this->validateConnectionSettings($connectionSettings);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'details' => []
                ];
            }

            // Create temporary database configuration
            $dbConfig = $this->createDatabaseConfig($connectionSettings);
            
            // Test basic connection
            $connectionTest = $this->performConnectionTest($dbConfig);
            if (!$connectionTest['success']) {
                return $connectionTest;
            }

            $db = $connectionTest['connection'];
            $connectionTime = microtime(true) - $startTime;

            // Perform comprehensive tests
            $results = [
                'success' => true,
                'connection_time' => round($connectionTime * 1000, 2), // milliseconds
                'database_info' => $this->getDatabaseInfo($db),
                'performance_tests' => $this->performPerformanceTests($db),
                'feature_tests' => $this->performFeatureTests($db),
                'table_tests' => $this->performTableTests($db, $connectionSettings),
                'security_tests' => $this->performSecurityTests($db),
            ];

            // Close test connection
            $db->close();

            return $results;

        } catch (\Exception $e) {
            log_message('error', 'Database test error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Database connection test failed: ' . $e->getMessage(),
                'details' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ];
        }
    }

    /**
     * Validate connection settings
     */
    private function validateConnectionSettings(array $settings): array
    {
        $requiredFields = ['DBDriver', 'hostname', 'database'];
        
        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}"
                ];
            }
        }

        // Validate driver
        if (!in_array($settings['DBDriver'], $this->supportedDrivers)) {
            return [
                'valid' => false,
                'error' => 'Unsupported database driver: ' . $settings['DBDriver']
            ];
        }

        // Validate hostname format
        if (!$this->isValidHostname($settings['hostname'])) {
            return [
                'valid' => false,
                'error' => 'Invalid hostname format'
            ];
        }

        // Validate port if provided
        if (!empty($settings['port'])) {
            $port = (int)$settings['port'];
            if ($port < 1 || $port > 65535) {
                return [
                    'valid' => false,
                    'error' => 'Invalid port number (1-65535)'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Create database configuration array
     */
    private function createDatabaseConfig(array $settings): array
    {
        $driver = strtolower($settings['DBDriver']);
        
        return [
            'DSN' => '',
            'hostname' => $settings['hostname'],
            'username' => $settings['username'] ?? '',
            'password' => $settings['password'] ?? '',
            'database' => $settings['database'],
            'DBDriver' => $settings['DBDriver'],
            'DBPrefix' => $settings['DBPrefix'] ?? '',
            'pConnect' => false,
            'DBDebug' => false,
            'charset' => $settings['charset'] ?? 'utf8mb4',
            'DBCollat' => $settings['DBCollat'] ?? 'utf8mb4_unicode_ci',
            'swapPre' => '',
            'encrypt' => $settings['encrypt'] ?? false,
            'compress' => $settings['compress'] ?? false,
            'strictOn' => $settings['strictOn'] ?? false,
            'failover' => [],
            'port' => $settings['port'] ?? $this->commonPorts[$driver] ?? 3306,
        ];
    }

    /**
     * Perform basic connection test
     */
    private function performConnectionTest(array $dbConfig): array
    {
        try {
            // Create database connection
            $db = \Config\Database::connect($dbConfig);
            
            // Test connection by running a simple query
            $result = $db->query("SELECT 1 as test");
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Failed to execute test query',
                    'db_error' => $db->error()
                ];
            }

            return [
                'success' => true,
                'connection' => $db
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
                'details' => [
                    'hostname' => $dbConfig['hostname'],
                    'database' => $dbConfig['database'],
                    'driver' => $dbConfig['DBDriver']
                ]
            ];
        }
    }

    /**
     * Get database information
     */
    private function getDatabaseInfo($db): array
    {
        try {
            $info = [];
            $platform = $db->getPlatform();

            switch (strtolower($platform)) {
                case 'mysql':
                case 'mysqli':
                    $info = $this->getMySQLInfo($db);
                    break;
                case 'postgre':
                    $info = $this->getPostgreSQLInfo($db);
                    break;
                case 'sqlite3':
                    $info = $this->getSQLiteInfo($db);
                    break;
                default:
                    $info['platform'] = $platform;
            }

            return $info;

        } catch (\Exception $e) {
            return ['error' => 'Could not retrieve database info: ' . $e->getMessage()];
        }
    }

    /**
     * Get MySQL database information
     */
    private function getMySQLInfo($db): array
    {
        try {
            $version = $db->query("SELECT VERSION() as version")->getRow();
            $charset = $db->query("SELECT @@character_set_database as charset")->getRow();
            $collation = $db->query("SELECT @@collation_database as collation")->getRow();
            $timezone = $db->query("SELECT @@time_zone as timezone")->getRow();
            
            return [
                'platform' => 'MySQL',
                'version' => $version->version ?? 'unknown',
                'charset' => $charset->charset ?? 'unknown',
                'collation' => $collation->collation ?? 'unknown',
                'timezone' => $timezone->timezone ?? 'unknown',
            ];

        } catch (\Exception $e) {
            return [
                'platform' => 'MySQL',
                'error' => 'Could not retrieve MySQL info: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get PostgreSQL database information
     */
    private function getPostgreSQLInfo($db): array
    {
        try {
            $version = $db->query("SELECT version()")->getRow();
            
            return [
                'platform' => 'PostgreSQL',
                'version' => $version->version ?? 'unknown',
            ];

        } catch (\Exception $e) {
            return [
                'platform' => 'PostgreSQL',
                'error' => 'Could not retrieve PostgreSQL info: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get SQLite database information
     */
    private function getSQLiteInfo($db): array
    {
        try {
            $version = $db->query("SELECT sqlite_version() as version")->getRow();
            
            return [
                'platform' => 'SQLite',
                'version' => $version->version ?? 'unknown',
            ];

        } catch (\Exception $e) {
            return [
                'platform' => 'SQLite',
                'error' => 'Could not retrieve SQLite info: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform performance tests
     */
    private function performPerformanceTests($db): array
    {
        $tests = [];

        try {
            // Test simple query performance
            $startTime = microtime(true);
            $db->query("SELECT 1");
            $tests['simple_query_time'] = round((microtime(true) - $startTime) * 1000, 2);

            // Test with small dataset
            $startTime = microtime(true);
            $db->query("SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5");
            $tests['small_dataset_time'] = round((microtime(true) - $startTime) * 1000, 2);

            // Connection pool test (if applicable)
            $startTime = microtime(true);
            for ($i = 0; $i < 5; $i++) {
                $db->query("SELECT {$i}");
            }
            $tests['multiple_queries_time'] = round((microtime(true) - $startTime) * 1000, 2);

        } catch (\Exception $e) {
            $tests['error'] = 'Performance test failed: ' . $e->getMessage();
        }

        return $tests;
    }

    /**
     * Perform feature tests
     */
    private function performFeatureTests($db): array
    {
        $features = [];

        try {
            // Test transaction support
            $db->transStart();
            $db->query("SELECT 1");
            $db->transComplete();
            $features['transactions'] = $db->transStatus();

            // Test prepared statements
            try {
                $stmt = $db->prepare(function($db) {
                    return $db->query("SELECT ? as test", [1]);
                });
                $features['prepared_statements'] = true;
            } catch (\Exception $e) {
                $features['prepared_statements'] = false;
            }

            // Test JSON support (MySQL 5.7+, PostgreSQL)
            try {
                $db->query("SELECT JSON_OBJECT('key', 'value') as json_test");
                $features['json_support'] = true;
            } catch (\Exception $e) {
                $features['json_support'] = false;
            }

        } catch (\Exception $e) {
            $features['error'] = 'Feature test failed: ' . $e->getMessage();
        }

        return $features;
    }

    /**
     * Perform table access tests
     */
    private function performTableTests($db, array $settings): array
    {
        $tests = [];

        try {
            // Test table listing
            $tables = $db->listTables();
            $tests['can_list_tables'] = true;
            $tests['table_count'] = count($tables);
            
            // Test if reward table exists and is accessible
            $rewardTable = ($settings['DBPrefix'] ?? '') . 'users'; // Test with a common table
            
            if (in_array($rewardTable, $tables)) {
                $tests['reward_table_exists'] = true;
                
                // Test table structure
                $fields = $db->getFieldNames($rewardTable);
                $tests['reward_table_fields'] = $fields;
                
                // Test read access
                try {
                    $result = $db->query("SELECT COUNT(*) as count FROM {$rewardTable} LIMIT 1");
                    $tests['reward_table_readable'] = true;
                    $tests['reward_table_row_count'] = $result->getRow()->count ?? 0;
                } catch (\Exception $e) {
                    $tests['reward_table_readable'] = false;
                    $tests['reward_table_error'] = $e->getMessage();
                }
                
            } else {
                $tests['reward_table_exists'] = false;
            }

        } catch (\Exception $e) {
            $tests['error'] = 'Table test failed: ' . $e->getMessage();
        }

        return $tests;
    }

    /**
     * Perform security tests
     */
    private function performSecurityTests($db): array
    {
        $tests = [];

        try {
            // Test SQL injection prevention
            $testValue = "'; DROP TABLE test; --";
            $result = $db->query("SELECT ? as safe_test", [$testValue]);
            $tests['sql_injection_safe'] = ($result !== false);

            // Test connection encryption (if configured)
            try {
                $ssl = $db->query("SHOW STATUS LIKE 'Ssl_cipher'")->getRow();
                $tests['ssl_enabled'] = !empty($ssl->Value ?? '');
            } catch (\Exception $e) {
                $tests['ssl_test_error'] = $e->getMessage();
            }

        } catch (\Exception $e) {
            $tests['error'] = 'Security test failed: ' . $e->getMessage();
        }

        return $tests;
    }

    /**
     * Test reward table mapping
     */
    public function testRewardTableMapping(array $connectionSettings, array $tableMapping): array
    {
        try {
            $dbConfig = $this->createDatabaseConfig($connectionSettings);
            $db = \Config\Database::connect($dbConfig);

            $results = [];
            
            foreach ($tableMapping as $type => $config) {
                $tableName = $config['table'];
                $results[$type] = $this->testTableMapping($db, $tableName, $config);
            }

            $db->close();
            
            return [
                'success' => true,
                'mapping_tests' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Table mapping test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test individual table mapping
     */
    private function testTableMapping($db, string $tableName, array $config): array
    {
        try {
            // Check if table exists
            if (!$db->tableExists($tableName)) {
                return [
                    'exists' => false,
                    'error' => "Table '{$tableName}' does not exist"
                ];
            }

            // Get table fields
            $fields = $db->getFieldNames($tableName);
            
            // Check required fields
            $requiredFields = $config['fields'] ?? [];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!in_array($field, $fields)) {
                    $missingFields[] = $field;
                }
            }

            // Test read access
            $readTest = false;
            try {
                $db->query("SELECT COUNT(*) FROM {$tableName} LIMIT 1");
                $readTest = true;
            } catch (\Exception $e) {
                // Read test failed
            }

            // Test write access (if configured)
            $writeTest = false;
            if ($config['test_write'] ?? false) {
                try {
                    $db->transStart();
                    // Try to insert a test record (will be rolled back)
                    $testData = $config['test_data'] ?? ['test_field' => 'test_value'];
                    $db->table($tableName)->insert($testData);
                    $writeTest = true;
                    $db->transRollback();
                } catch (\Exception $e) {
                    $db->transRollback();
                }
            }

            return [
                'exists' => true,
                'fields' => $fields,
                'required_fields_present' => empty($missingFields),
                'missing_fields' => $missingFields,
                'readable' => $readTest,
                'writable' => $writeTest,
            ];

        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => 'Table mapping test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate connection test report
     */
    public function generateTestReport(array $testResults): string
    {
        $report = "=== Database Connection Test Report ===\n\n";
        
        if ($testResults['success']) {
            $report .= "✅ Connection Status: SUCCESS\n";
            $report .= "⏱️ Connection Time: {$testResults['connection_time']}ms\n\n";
            
            // Database Info
            $report .= "📊 Database Information:\n";
            foreach ($testResults['database_info'] as $key => $value) {
                $report .= "   {$key}: {$value}\n";
            }
            $report .= "\n";
            
            // Performance Tests
            $report .= "🚀 Performance Tests:\n";
            foreach ($testResults['performance_tests'] as $key => $value) {
                $report .= "   {$key}: {$value}" . (is_numeric($value) ? "ms" : "") . "\n";
            }
            $report .= "\n";
            
            // Feature Tests
            $report .= "🔧 Feature Tests:\n";
            foreach ($testResults['feature_tests'] as $key => $value) {
                $status = is_bool($value) ? ($value ? "✅" : "❌") : $value;
                $report .= "   {$key}: {$status}\n";
            }
            
        } else {
            $report .= "❌ Connection Status: FAILED\n";
            $report .= "Error: {$testResults['error']}\n";
        }
        
        return $report;
    }

    /**
     * Validate hostname format
     */
    private function isValidHostname(string $hostname): bool
    {
        // Allow localhost, IP addresses, and domain names
        return filter_var($hostname, FILTER_VALIDATE_IP) !== false || 
               filter_var($hostname, FILTER_VALIDATE_DOMAIN) !== false ||
               $hostname === 'localhost';
    }
}