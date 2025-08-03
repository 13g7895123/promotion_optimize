<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class CorsTestController extends BaseController
{
    use ResponseTrait;

    /**
     * Simple CORS test endpoint
     */
    public function test(): ResponseInterface
    {
        $response = [
            'status' => 'success',
            'message' => 'CORS test successful',
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'method' => $this->request->getMethod(),
                'origin' => $this->request->getHeaderLine('Origin'),
                'user_agent' => $this->request->getHeaderLine('User-Agent'),
                'headers' => $this->request->getHeaders(),
            ],
        ];

        return $this->respond($response);
    }

    /**
     * OPTIONS handler for preflight requests
     */
    public function options(): ResponseInterface
    {
        $response = [
            'status' => 'success',
            'message' => 'OPTIONS preflight successful',
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'allowed_methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD',
                'allowed_headers' => '*',
            ],
        ];

        return $this->respond($response, 200);
    }

    /**
     * Test POST endpoint
     */
    public function testPost(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        
        $response = [
            'status' => 'success',
            'message' => 'POST test successful',
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'received_data' => $data,
                'content_type' => $this->request->getHeaderLine('Content-Type'),
            ],
        ];

        return $this->respond($response);
    }
}