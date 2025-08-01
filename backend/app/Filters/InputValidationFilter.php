<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InputValidationFilter implements FilterInterface
{
    private array $sqlInjectionPatterns;
    private array $xssPatterns;

    public function __construct()
    {
        $config = config('Security');
        $this->sqlInjectionPatterns = $config->sqlInjectionPatterns;
        
        $this->xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
        ];
    }

    /**
     * Validate and sanitize input before processing
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
            // Skip validation for certain content types
            if ($this->shouldSkipValidation($request)) {
                return $request;
            }

            // Validate request headers
            if (!$this->validateHeaders($request)) {
                return $this->securityViolationResponse($response, 'Invalid headers detected');
            }

            // Get request data
            $data = $this->getRequestData($request);

            if (!empty($data)) {
                // Check for SQL injection attempts
                if ($this->detectSQLInjection($data)) {
                    log_message('security', 'SQL Injection attempt detected from IP: ' . $request->getIPAddress());
                    return $this->securityViolationResponse($response, 'Malicious input detected');
                }

                // Check for XSS attempts
                if ($this->detectXSS($data)) {
                    log_message('security', 'XSS attempt detected from IP: ' . $request->getIPAddress());
                    return $this->securityViolationResponse($response, 'Malicious script detected');
                }

                // Validate file uploads
                if (!$this->validateFileUploads($request)) {
                    return $this->securityViolationResponse($response, 'Invalid file upload detected');
                }

                // Validate JSON structure for API endpoints
                if (strpos($request->getUri()->getPath(), '/api/') !== false) {
                    if (!$this->validateJSONStructure($request)) {
                        return $this->badRequestResponse($response, 'Invalid JSON structure');
                    }
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Input validation error: ' . $e->getMessage());
            return $this->securityViolationResponse($response, 'Input validation failed');
        }

        return $request;
    }

    /**
     * Add security headers after processing
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add additional security headers
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }

    /**
     * Check if validation should be skipped
     */
    private function shouldSkipValidation(RequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        // Skip for file uploads (multipart/form-data)
        if (strpos($contentType, 'multipart/form-data') !== false) {
            return false; // Don't skip, we need to validate file uploads
        }

        // Skip for certain endpoints if needed
        $uri = $request->getUri()->getPath();
        $skipRoutes = [
            // Add routes that should skip validation if needed
        ];

        return in_array($uri, $skipRoutes);
    }

    /**
     * Validate request headers
     */
    private function validateHeaders(RequestInterface $request): bool
    {
        $headers = $request->getHeaders();

        foreach ($headers as $name => $values) {
            $headerValue = implode(', ', $values);
            
            // Check for malicious header content
            if ($this->containsMaliciousContent($headerValue)) {
                return false;
            }

            // Check header length
            if (strlen($headerValue) > 8192) { // 8KB limit
                return false;
            }
        }

        return true;
    }

    /**
     * Get request data for validation
     */
    private function getRequestData(RequestInterface $request): array
    {
        $data = [];

        // GET parameters
        $data = array_merge($data, $request->getGet() ?? []);

        // POST data
        if ($request->getMethod() === 'POST') {
            $postData = $request->getPost() ?? [];
            $data = array_merge($data, $postData);

            // JSON data
            $json = $request->getJSON(true);
            if ($json) {
                $data = array_merge($data, $json);
            }
        }

        // PUT/PATCH data
        if (in_array($request->getMethod(), ['PUT', 'PATCH'])) {
            $inputData = $request->getJSON(true) ?? [];
            $data = array_merge($data, $inputData);
        }

        return $data;
    }

    /**
     * Detect SQL injection attempts
     */
    private function detectSQLInjection($data): bool
    {
        $content = is_array($data) ? json_encode($data) : (string) $data;
        $content = strtolower($content);

        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect XSS attempts
     */
    private function detectXSS($data): bool
    {
        $content = is_array($data) ? json_encode($data) : (string) $data;

        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate file uploads
     */
    private function validateFileUploads(RequestInterface $request): bool
    {
        $files = $request->getFiles();
        
        if (empty($files)) {
            return true;
        }

        $config = config('Security');
        $uploadConfig = $config->uploadSecurity;

        foreach ($files as $file) {
            if ($file->isValid()) {
                // Check file size
                if ($file->getSize() > $uploadConfig['max_size'] * 1024) {
                    return false;
                }

                // Check file type
                $allowedTypes = explode('|', $uploadConfig['allowed_types']);
                $fileExtension = $file->getClientExtension();
                
                if (!in_array($fileExtension, $allowedTypes)) {
                    return false;
                }

                // Check for malicious file content
                if ($this->isMaliciousFile($file)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate JSON structure
     */
    private function validateJSONStructure(RequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = $request->getBody();
            
            if (!empty($json) && json_decode($json) === null && json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for malicious content in strings
     */
    private function containsMaliciousContent(string $content): bool
    {
        // Check for common attack patterns
        $maliciousPatterns = [
            '/\.\.\//i', // Directory traversal
            '/\0/i', // Null bytes
            '/<script/i', // Script tags
            '/javascript:/i', // JavaScript protocol
            '/data:text\/html/i', // Data URI with HTML
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if uploaded file is malicious
     */
    private function isMaliciousFile($file): bool
    {
        // Read first few bytes to check file signature
        $handle = fopen($file->getTempName(), 'rb');
        $header = fread($handle, 1024);
        fclose($handle);

        // Check for executable signatures
        $executableSignatures = [
            "\x4D\x5A", // Windows PE
            "\x7F\x45\x4C\x46", // Linux ELF
            "\xCA\xFE\xBA\xBE", // Java Class
            "\xFE\xED\xFA", // Mach-O
        ];

        foreach ($executableSignatures as $signature) {
            if (strpos($header, $signature) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return security violation response
     */
    private function securityViolationResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'code' => 400,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $response->setStatusCode(400)
                       ->setJSON($data);
    }

    /**
     * Return bad request response
     */
    private function badRequestResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'code' => 400,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $response->setStatusCode(400)
                       ->setJSON($data);
    }
}