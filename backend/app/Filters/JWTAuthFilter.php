<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTAuth;
use Firebase\JWT\InvalidTokenException;

class JWTAuthFilter implements FilterInterface
{
    private JWTAuth $jwtAuth;

    public function __construct()
    {
        $this->jwtAuth = new JWTAuth();
    }

    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');

        try {
            // Extract token from request
            $token = $this->jwtAuth->getTokenFromRequest($request);

            if (!$token) {
                return $this->unauthorizedResponse($response, 'Missing authorization token');
            }

            // Validate token
            $payload = $this->jwtAuth->validateToken($token);

            if (!$payload) {
                return $this->unauthorizedResponse($response, 'Invalid token');
            }

            // Store user data in request for controllers to use
            $request->userPayload = $payload;

            // Check if user is active
            if (!$this->isUserActive($payload['user_id'])) {
                return $this->unauthorizedResponse($response, 'User account is not active');
            }

        } catch (InvalidTokenException $e) {
            return $this->unauthorizedResponse($response, $e->getMessage());
        } catch (\Exception $e) {
            log_message('error', 'JWT Auth Filter Error: ' . $e->getMessage());
            return $this->unauthorizedResponse($response, 'Authentication failed');
        }

        return $request;
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add security headers
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        
        return $response;
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'code' => 401,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $response->setStatusCode(401)
                       ->setJSON($data);
    }

    /**
     * Check if user is active
     */
    private function isUserActive(int $userId): bool
    {
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        return $user && $user['status'] === 'active' && !$user['deleted_at'];
    }
}