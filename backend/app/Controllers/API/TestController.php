<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class TestController extends BaseController
{
    use ResponseTrait;

    /**
     * Test API connectivity
     */
    public function index(): ResponseInterface
    {
        return $this->respond([
            'success' => true,
            'message' => 'API 連接正常',
            'data' => [
                'server_time' => date('Y-m-d H:i:s'),
                'environment' => ENVIRONMENT,
                'version' => '1.0.0'
            ]
        ]);
    }

    /**
     * Health check endpoint
     */
    public function health(): ResponseInterface
    {
        return $this->respond([
            'success' => true,
            'status' => 'healthy',
            'data' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'timestamp' => time()
            ]
        ]);
    }

    /**
     * Test database connection
     */
    private function checkDatabase(): array
    {
        try {
            $db = \Config\Database::connect();
            $query = $db->query('SELECT 1 as test');
            $result = $query->getRow();
            
            return [
                'status' => 'connected',
                'test_query' => $result ? 'success' : 'failed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Test Redis connection
     */
    private function checkRedis(): array
    {
        try {
            $redis = \Config\Services::redis();
            $redis->ping();
            
            return [
                'status' => 'connected',
                'ping' => 'success'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}