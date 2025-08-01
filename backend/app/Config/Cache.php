<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cache extends BaseConfig
{
    public string $handler = 'redis';

    public int $backupHandler = 'file';

    public ?string $prefix = '';

    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    public array $redis = [
        'host'     => 'localhost',
        'password' => '',
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    public array $validHandlers = [
        'dummy'     => \CodeIgniter\Cache\Handlers\DummyHandler::class,
        'file'      => \CodeIgniter\Cache\Handlers\FileHandler::class,
        'memcached' => \CodeIgniter\Cache\Handlers\MemcachedHandler::class,
        'predis'    => \CodeIgniter\Cache\Handlers\PredisHandler::class,
        'redis'     => \CodeIgniter\Cache\Handlers\RedisHandler::class,
        'wincache'  => \CodeIgniter\Cache\Handlers\WincacheHandler::class,
    ];

    // Cache configurations for different data types
    public array $cacheGroups = [
        'users' => [
            'ttl' => 3600, // 1 hour
            'prefix' => 'user_',
        ],
        'sessions' => [
            'ttl' => 7200, // 2 hours
            'prefix' => 'sess_',
        ],
        'permissions' => [
            'ttl' => 86400, // 24 hours
            'prefix' => 'perm_',
        ],
        'servers' => [
            'ttl' => 1800, // 30 minutes
            'prefix' => 'srv_',
        ],
        'api_responses' => [
            'ttl' => 300, // 5 minutes
            'prefix' => 'api_',
        ],
    ];

    public function __construct()
    {
        // Load Redis configuration from environment
        $this->redis['host'] = env('REDIS_HOST', 'localhost');
        $this->redis['port'] = env('REDIS_PORT', 6379);
        $this->redis['password'] = env('REDIS_PASSWORD', '');
        $this->redis['database'] = env('REDIS_DATABASE', 0);

        // Use file cache as fallback in development if Redis is not available
        if (ENVIRONMENT === 'development' && !extension_loaded('redis')) {
            $this->handler = 'file';
        }
    }
}