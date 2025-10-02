<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

/**
 * Simple Session-based Authentication Filter
 * Works with SimpleAuthController
 */
class SessionAuthFilter implements FilterInterface
{
    use ResponseTrait;

    /**
     * Do whatever processing this filter needs to do.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            // Get authorization header
            $authHeader = $request->getHeaderLine('Authorization');

            if (empty($authHeader)) {
                return service('response')
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Authorization header required',
                    ])
                    ->setStatusCode(401);
            }

            // Extract token
            $token = str_replace('Bearer ', '', $authHeader);

            if (empty($token)) {
                return service('response')
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Token is required',
                    ])
                    ->setStatusCode(401);
            }

            // Validate session token
            $db = \Config\Database::connect();
            $session = $db->table('user_sessions')
                ->where('session_token', $token)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()
                ->getRowArray();

            if (!$session) {
                return service('response')
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Invalid or expired token',
                    ])
                    ->setStatusCode(401);
            }

            // Get user
            $user = $db->table('users')
                ->where('id', $session['user_id'])
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$user || $user['status'] !== 'active') {
                return service('response')
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not found or inactive',
                    ])
                    ->setStatusCode(401);
            }

            // Store user in request for later use
            $request->user = $user;
            $request->session = $session;

        } catch (\Exception $e) {
            log_message('error', 'Session auth error: ' . $e->getMessage());
            return service('response')
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Authentication failed',
                ])
                ->setStatusCode(500);
        }
    }

    /**
     * We don't need to do anything here.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
