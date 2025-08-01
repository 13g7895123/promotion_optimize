<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CORSFilter implements FilterInterface
{
    /**
     * Handle CORS preflight and actual requests
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config('App');
        $corsConfig = $config->corsConfig;

        $response = service('response');
        $origin = $request->getHeaderLine('Origin');

        // Check if origin is allowed
        if ($origin && in_array($origin, $corsConfig['allowedOrigins'])) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        } elseif (in_array('*', $corsConfig['allowedOrigins'])) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        }

        // Set CORS headers
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowedMethods']));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowedHeaders']));
        
        if (!empty($corsConfig['exposedHeaders'])) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $corsConfig['exposedHeaders']));
        }

        if ($corsConfig['supportsCredentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($corsConfig['maxAge'] > 0) {
            $response->setHeader('Access-Control-Max-Age', $corsConfig['maxAge']);
        }

        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $response->setStatusCode(200);
        }

        return $request;
    }

    /**
     * Handle CORS in response
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}