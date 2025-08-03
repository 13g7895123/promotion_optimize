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

        // Handle wildcard or specific origins
        if (in_array('*', $corsConfig['allowedOrigins'])) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        } elseif ($origin && in_array($origin, $corsConfig['allowedOrigins'])) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        }

        // Set CORS headers - handle wildcards
        if (in_array('*', $corsConfig['allowedMethods'])) {
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD');
        } else {
            $response->setHeader('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowedMethods']));
        }
        
        if (in_array('*', $corsConfig['allowedHeaders'])) {
            $response->setHeader('Access-Control-Allow-Headers', '*');
        } else {
            $response->setHeader('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowedHeaders']));
        }
        
        if (!empty($corsConfig['exposedHeaders']) && !in_array('*', $corsConfig['exposedHeaders'])) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $corsConfig['exposedHeaders']));
        } elseif (in_array('*', $corsConfig['exposedHeaders'])) {
            $response->setHeader('Access-Control-Expose-Headers', '*');
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