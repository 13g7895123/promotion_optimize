<?php

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $defaultGroup = 'default';

    public array $default = [
        'DSN'          => '',
        'hostname'     => 'mysql',
        'username'     => 'promotion_user',
        'password'     => 'promotion_password',
        'database'     => 'promotion_platform',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_unicode_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => 'root',
        'password'    => '',
        'database'    => 'ci4_test',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8mb4',
        'DBCollat'    => 'utf8mb4_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // Load environment-specific database configuration
        if (ENVIRONMENT === 'development') {
            $this->default['hostname'] = env('DB_HOST', 'localhost');
            $this->default['username'] = env('DB_USER', 'promotion_user');
            $this->default['password'] = env('DB_PASSWORD', 'promotion_password');
            $this->default['database'] = env('DB_NAME', 'promotion_platform');
            $this->default['port']     = env('DB_PORT', 3306);
        }

        // Enable query logging in development
        if (ENVIRONMENT === 'development') {
            $this->default['DBDebug'] = true;
        }

        // Production optimizations
        if (ENVIRONMENT === 'production') {
            $this->default['DBDebug'] = false;
            $this->default['pConnect'] = true; // Enable persistent connections
        }
    }
}