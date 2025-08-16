<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Home extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
    }

    /**
     * Default index method - API Information Page
     *
     * @return mixed
     */
    public function index()
    {
        // Get system information
        $systemInfo = [
            'api_name' => '私人遊戲伺服器推廣平台 API',
            'version' => '1.0.0',
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'php_version' => PHP_VERSION,
            'environment' => ENVIRONMENT,
            'timezone' => date_default_timezone_get(),
            'current_time' => date('Y-m-d H:i:s'),
            'base_url' => base_url(),
        ];

        // API endpoints information
        $apiEndpoints = [
            'authentication' => [
                'base_path' => '/api/auth',
                'endpoints' => [
                    'POST /api/auth/register' => 'User registration',
                    'POST /api/auth/login' => 'User login',
                    'POST /api/auth/refresh' => 'Refresh JWT token',
                    'POST /api/auth/logout' => 'User logout (requires auth)',
                    'GET /api/auth/profile' => 'Get user profile (requires auth)',
                    'PUT /api/auth/profile' => 'Update user profile (requires auth)',
                    'POST /api/auth/change-password' => 'Change password (requires auth)',
                ]
            ],
            'servers' => [
                'base_path' => '/api/servers',
                'description' => 'Game server management',
                'auth_required' => true
            ],
            'promotions' => [
                'base_path' => '/api/promotions',
                'description' => 'Promotion system management',
                'auth_required' => true
            ],
            'rewards' => [
                'base_path' => '/api/rewards',
                'description' => 'Reward system management',
                'auth_required' => true
            ],
            'statistics' => [
                'base_path' => '/api/statistics',
                'description' => 'Analytics and reporting',
                'auth_required' => true
            ],
            'public_endpoints' => [
                'GET /api/test' => 'API test endpoint',
                'GET /api/health' => 'Health check endpoint',
                'GET /api/cors-test' => 'CORS configuration test',
                'GET /api/r/{code}' => 'Promotion link redirect',
            ]
        ];

        // Health check information
        $healthInfo = [
            'status' => 'operational',
            'database' => $this->checkDatabaseConnection(),
            'cache' => $this->checkCacheConnection(),
            'storage' => $this->checkStorageAccess(),
        ];

        // Response data
        $responseData = [
            'system' => $systemInfo,
            'api_endpoints' => $apiEndpoints,
            'health' => $healthInfo,
            'documentation' => [
                'openapi_spec' => base_url('docs/api/openapi.yaml'),
                'readme' => base_url('docs/README.md'),
            ],
            'support' => [
                'contact' => 'admin@promotion-platform.com',
                'documentation' => 'https://docs.promotion-platform.com',
            ]
        ];

        // Check if request accepts JSON (API call)
        if ($this->request->isAJAX() || strpos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response->setJSON($responseData);
        }

        // Return HTML page for browser requests
        return $this->generateHtmlResponse($responseData);
    }

    /**
     * Generate HTML response for browser requests
     *
     * @param array $data
     * @return string
     */
    private function generateHtmlResponse(array $data): string
    {
        $html = '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $data['system']['api_name'] . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6; 
            color: #333; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 2rem;
        }
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .header h1 { 
            color: #2c3e50; 
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }
        .header p { 
            color: #7f8c8d; 
            font-size: 1.1rem;
        }
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 1.5rem; 
        }
        .card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .card h2 { 
            color: #2c3e50; 
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        .status { 
            display: inline-block; 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status.operational { 
            background: #d4edda; 
            color: #155724; 
        }
        .status.error { 
            background: #f8d7da; 
            color: #721c24; 
        }
        .endpoint { 
            background: #f8f9fa; 
            border-left: 4px solid #3498db;
            padding: 0.75rem; 
            margin: 0.5rem 0; 
            border-radius: 0 8px 8px 0;
            font-family: "Monaco", "Menlo", monospace;
            font-size: 0.9rem;
        }
        .method { 
            color: #fff; 
            padding: 0.2rem 0.5rem; 
            border-radius: 4px; 
            font-size: 0.8rem; 
            font-weight: bold;
            margin-right: 0.5rem;
        }
        .method.get { background: #28a745; }
        .method.post { background: #007bff; }
        .method.put { background: #ffc107; color: #212529; }
        .method.delete { background: #dc3545; }
        .info-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 0.5rem 0; 
            border-bottom: 1px solid #eee; 
        }
        .info-item:last-child { border-bottom: none; }
        .info-label { 
            font-weight: 600; 
            color: #495057; 
        }
        .info-value { 
            color: #6c757d; 
            font-family: "Monaco", "Menlo", monospace;
        }
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header h1 { font-size: 2rem; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . $data['system']['api_name'] . '</h1>
            <p>版本 ' . $data['system']['version'] . ' | CodeIgniter ' . $data['system']['codeigniter_version'] . ' | PHP ' . $data['system']['php_version'] . '</p>
            <p>環境: <strong>' . $data['system']['environment'] . '</strong> | 當前時間: ' . $data['system']['current_time'] . '</p>
        </div>

        <div class="grid">
            <div class="card">
                <h2>🏥 系統健康狀態</h2>
                <div class="info-item">
                    <span class="info-label">整體狀態:</span>
                    <span class="status ' . $data['health']['status'] . '">' . $data['health']['status'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">資料庫:</span>
                    <span class="status ' . ($data['health']['database'] ? 'operational' : 'error') . '">' . ($data['health']['database'] ? '正常' : '異常') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">快取系統:</span>
                    <span class="status ' . ($data['health']['cache'] ? 'operational' : 'error') . '">' . ($data['health']['cache'] ? '正常' : '異常') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">檔案存儲:</span>
                    <span class="status ' . ($data['health']['storage'] ? 'operational' : 'error') . '">' . ($data['health']['storage'] ? '正常' : '異常') . '</span>
                </div>
            </div>

            <div class="card">
                <h2>🔐 認證相關 API</h2>';
                
        foreach ($data['api_endpoints']['authentication']['endpoints'] as $endpoint => $description) {
            $parts = explode(' ', $endpoint, 2);
            $method = strtolower($parts[0]);
            $path = $parts[1] ?? '';
            $html .= '<div class="endpoint">
                        <span class="method ' . $method . '">' . strtoupper($method) . '</span>
                        <span>' . $path . '</span>
                        <br><small style="color: #6c757d; margin-left: 3rem;">' . $description . '</small>
                      </div>';
        }

        $html .= '</div>

            <div class="card">
                <h2>🌐 公共 API 端點</h2>';
                
        foreach ($data['api_endpoints']['public_endpoints'] as $endpoint => $description) {
            $parts = explode(' ', $endpoint, 2);
            $method = strtolower($parts[0]);
            $path = $parts[1] ?? '';
            $html .= '<div class="endpoint">
                        <span class="method ' . $method . '">' . strtoupper($method) . '</span>
                        <span>' . $path . '</span>
                        <br><small style="color: #6c757d; margin-left: 3rem;">' . $description . '</small>
                      </div>';
        }

        $html .= '</div>

            <div class="card">
                <h2>🛠️ 主要功能模組</h2>
                <div class="info-item">
                    <span class="info-label">伺服器管理:</span>
                    <span class="info-value">/api/servers</span>
                </div>
                <div class="info-item">
                    <span class="info-label">推廣系統:</span>
                    <span class="info-value">/api/promotions</span>
                </div>
                <div class="info-item">
                    <span class="info-label">獎勵系統:</span>
                    <span class="info-value">/api/rewards</span>
                </div>
                <div class="info-item">
                    <span class="info-label">統計分析:</span>
                    <span class="info-value">/api/statistics</span>
                </div>
                <p style="margin-top: 1rem; color: #6c757d; font-size: 0.9rem;">
                    <strong>注意:</strong> 大部分 API 端點需要 JWT 認證令牌
                </p>
            </div>

            <div class="card">
                <h2>📚 文檔與支援</h2>
                <div class="info-item">
                    <span class="info-label">API 規格:</span>
                    <span class="info-value">
                        <a href="' . $data['documentation']['openapi_spec'] . '" target="_blank" style="color: #3498db; text-decoration: none;">OpenAPI YAML</a>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">說明文件:</span>
                    <span class="info-value">
                        <a href="' . $data['documentation']['readme'] . '" target="_blank" style="color: #3498db; text-decoration: none;">README</a>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">技術支援:</span>
                    <span class="info-value">' . $data['support']['contact'] . '</span>
                </div>
            </div>

            <div class="card">
                <h2>⚙️ 系統資訊</h2>
                <div class="info-item">
                    <span class="info-label">基礎 URL:</span>
                    <span class="info-value">' . $data['system']['base_url'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">時區:</span>
                    <span class="info-value">' . $data['system']['timezone'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">環境:</span>
                    <span class="info-value">' . $data['system']['environment'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">PHP 版本:</span>
                    <span class="info-value">' . $data['system']['php_version'] . '</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2024 私人遊戲伺服器推廣平台. 由 CodeIgniter ' . $data['system']['codeigniter_version'] . ' 強力驅動</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Check database connection
     *
     * @return bool
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            $db = \Config\Database::connect();
            return $db->initialize();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check cache connection (Redis if configured)
     *
     * @return bool
     */
    private function checkCacheConnection(): bool
    {
        try {
            $cache = \Config\Services::cache();
            $cache->save('health_check', 'test', 1);
            return $cache->get('health_check') === 'test';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check storage access
     *
     * @return bool
     */
    private function checkStorageAccess(): bool
    {
        try {
            $testFile = WRITEPATH . 'test_' . time() . '.tmp';
            $result = file_put_contents($testFile, 'test');
            if ($result !== false && file_exists($testFile)) {
                unlink($testFile);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}