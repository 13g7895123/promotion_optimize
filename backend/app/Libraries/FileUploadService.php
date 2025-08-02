<?php

namespace App\Libraries;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;

class FileUploadService
{
    private array $config;
    private string $uploadPath;

    public function __construct()
    {
        $this->config = [
            'max_file_size' => 5 * 1024 * 1024, // 5MB
            'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'image_dimensions' => [
                'logo' => ['max_width' => 500, 'max_height' => 500],
                'background' => ['max_width' => 1920, 'max_height' => 1080],
                'banner' => ['max_width' => 1200, 'max_height' => 400],
            ],
            'thumbnail_sizes' => [
                'small' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 300, 'height' => 300],
                'large' => ['width' => 600, 'height' => 600],
            ],
        ];

        $this->uploadPath = FCPATH . 'uploads/servers/';
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload server image file
     */
    public function uploadServerImage(UploadedFile $file, int $serverId, string $imageType = 'logo'): array
    {
        try {
            // Validate file
            $validation = $this->validateImageFile($file, $imageType);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // Generate file name
            $fileName = $this->generateFileName($serverId, $imageType, $file->getExtension());
            $filePath = $this->uploadPath . $fileName;

            // Move file
            if (!$file->move($this->uploadPath, $fileName)) {
                return ['success' => false, 'error' => 'Failed to move uploaded file'];
            }

            // Process image (resize, optimize)
            $processedFile = $this->processImage($filePath, $imageType);
            if (!$processedFile['success']) {
                return $processedFile;
            }

            // Generate thumbnails
            $thumbnails = $this->generateThumbnails($filePath, $serverId, $imageType);

            $result = [
                'success' => true,
                'file_path' => $fileName,
                'file_url' => base_url('uploads/servers/' . $fileName),
                'file_size' => filesize($filePath),
                'dimensions' => $processedFile['dimensions'],
                'thumbnails' => $thumbnails,
            ];

            // Clean up old files if replacing
            $this->cleanupOldFiles($serverId, $imageType, $fileName);

            return $result;

        } catch (\Exception $e) {
            log_message('error', 'File upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Upload multiple banner images
     */
    public function uploadBannerImages(array $files, int $serverId): array
    {
        $results = [];
        $successful = 0;
        $errors = [];

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                $errors[] = "File {$index}: Invalid file";
                continue;
            }

            $result = $this->uploadServerImage($file, $serverId, 'banner');
            
            if ($result['success']) {
                $results[] = $result;
                $successful++;
            } else {
                $errors[] = "File {$index}: " . $result['error'];
            }
        }

        return [
            'success' => $successful > 0,
            'uploaded_count' => $successful,
            'total_count' => count($files),
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Validate image file
     */
    private function validateImageFile(UploadedFile $file, string $imageType): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return ['valid' => false, 'error' => 'Invalid file upload'];
        }

        // Check file size
        if ($file->getSize() > $this->config['max_file_size']) {
            $maxSizeMB = round($this->config['max_file_size'] / 1024 / 1024, 1);
            return ['valid' => false, 'error' => "File size exceeds {$maxSizeMB}MB limit"];
        }

        // Check file extension
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, $this->config['allowed_image_types'])) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $this->config['allowed_image_types'])];
        }

        // Check MIME type
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return ['valid' => false, 'error' => 'Invalid MIME type'];
        }

        // Check image dimensions
        if (isset($this->config['image_dimensions'][$imageType])) {
            $imageInfo = getimagesize($file->getTempName());
            if ($imageInfo === false) {
                return ['valid' => false, 'error' => 'Cannot read image dimensions'];
            }

            $limits = $this->config['image_dimensions'][$imageType];
            if ($imageInfo[0] > $limits['max_width'] || $imageInfo[1] > $limits['max_height']) {
                return ['valid' => false, 'error' => "Image dimensions exceed {$limits['max_width']}x{$limits['max_height']} limit"];
            }
        }

        return ['valid' => true];
    }

    /**
     * Process image (resize, optimize)
     */
    private function processImage(string $filePath, string $imageType): array
    {
        try {
            $imageService = \Config\Services::image();
            
            // Get current dimensions
            $info = getimagesize($filePath);
            if ($info === false) {
                return ['success' => false, 'error' => 'Cannot read image info'];
            }

            $currentWidth = $info[0];
            $currentHeight = $info[1];

            // Check if resizing is needed
            $maxDimensions = $this->config['image_dimensions'][$imageType] ?? null;
            if ($maxDimensions && ($currentWidth > $maxDimensions['max_width'] || $currentHeight > $maxDimensions['max_height'])) {
                
                // Calculate new dimensions maintaining aspect ratio
                $ratio = min(
                    $maxDimensions['max_width'] / $currentWidth,
                    $maxDimensions['max_height'] / $currentHeight
                );
                
                $newWidth = intval($currentWidth * $ratio);
                $newHeight = intval($currentHeight * $ratio);

                // Resize image
                $imageService->withFile($filePath)
                           ->resize($newWidth, $newHeight, true, 'height')
                           ->save($filePath, 85); // 85% quality

                $currentWidth = $newWidth;
                $currentHeight = $newHeight;
            }

            return [
                'success' => true,
                'dimensions' => [
                    'width' => $currentWidth,
                    'height' => $currentHeight,
                ],
            ];

        } catch (\Exception $e) {
            log_message('error', 'Image processing error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to process image'];
        }
    }

    /**
     * Generate thumbnails
     */
    private function generateThumbnails(string $originalPath, int $serverId, string $imageType): array
    {
        $thumbnails = [];

        try {
            $imageService = \Config\Services::image();
            $originalFile = new File($originalPath);
            $baseName = $originalFile->getBasename('.' . $originalFile->getExtension());
            $extension = $originalFile->getExtension();

            foreach ($this->config['thumbnail_sizes'] as $size => $dimensions) {
                $thumbName = "{$baseName}_{$size}.{$extension}";
                $thumbPath = $this->uploadPath . $thumbName;

                $imageService->withFile($originalPath)
                           ->fit($dimensions['width'], $dimensions['height'])
                           ->save($thumbPath, 80);

                $thumbnails[$size] = [
                    'file_path' => $thumbName,
                    'file_url' => base_url('uploads/servers/' . $thumbName),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'file_size' => filesize($thumbPath),
                ];
            }

        } catch (\Exception $e) {
            log_message('error', 'Thumbnail generation error: ' . $e->getMessage());
        }

        return $thumbnails;
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(int $serverId, string $imageType, string $extension): string
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        return "server_{$serverId}_{$imageType}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Clean up old files
     */
    private function cleanupOldFiles(int $serverId, string $imageType, string $newFileName): void
    {
        try {
            $pattern = $this->uploadPath . "server_{$serverId}_{$imageType}_*";
            $oldFiles = glob($pattern);

            foreach ($oldFiles as $oldFile) {
                $oldBaseName = basename($oldFile);
                
                // Don't delete the new file
                if ($oldBaseName === $newFileName) {
                    continue;
                }

                // Delete old file and its thumbnails
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }

                // Delete thumbnails
                $fileInfo = pathinfo($oldFile);
                foreach (array_keys($this->config['thumbnail_sizes']) as $size) {
                    $thumbFile = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . "_{$size}." . $fileInfo['extension'];
                    if (file_exists($thumbFile)) {
                        unlink($thumbFile);
                    }
                }
            }

        } catch (\Exception $e) {
            log_message('warning', 'File cleanup error: ' . $e->getMessage());
        }
    }

    /**
     * Delete server files
     */
    public function deleteServerFiles(int $serverId, ?string $imageType = null): bool
    {
        try {
            if ($imageType) {
                $pattern = $this->uploadPath . "server_{$serverId}_{$imageType}_*";
            } else {
                $pattern = $this->uploadPath . "server_{$serverId}_*";
            }

            $files = glob($pattern);
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            return true;

        } catch (\Exception $e) {
            log_message('error', 'File deletion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $fileName): ?array
    {
        $filePath = $this->uploadPath . $fileName;
        
        if (!file_exists($filePath)) {
            return null;
        }

        $file = new File($filePath);
        $imageInfo = getimagesize($filePath);

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_url' => base_url('uploads/servers/' . $fileName),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getExtension(),
            'dimensions' => $imageInfo ? [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ] : null,
            'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
        ];
    }

    /**
     * Validate image URL from external source
     */
    public function validateImageUrl(string $url): array
    {
        try {
            $headers = get_headers($url, 1);
            
            if (!$headers || strpos($headers[0], '200') === false) {
                return ['valid' => false, 'error' => 'Image URL is not accessible'];
            }

            $contentType = $headers['Content-Type'] ?? '';
            if (is_array($contentType)) {
                $contentType = end($contentType);
            }

            if (!str_starts_with($contentType, 'image/')) {
                return ['valid' => false, 'error' => 'URL does not point to an image'];
            }

            $contentLength = $headers['Content-Length'] ?? 0;
            if (is_array($contentLength)) {
                $contentLength = end($contentLength);
            }

            if ($contentLength > $this->config['max_file_size']) {
                return ['valid' => false, 'error' => 'Image file size is too large'];
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Failed to validate image URL'];
        }
    }

    /**
     * Get upload statistics
     */
    public function getUploadStats(): array
    {
        try {
            $files = glob($this->uploadPath . '*');
            $totalSize = 0;
            $fileCount = 0;
            $typeStats = [];

            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileCount++;
                    $totalSize += filesize($file);
                    
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    $typeStats[$extension] = ($typeStats[$extension] ?? 0) + 1;
                }
            }

            return [
                'total_files' => $fileCount,
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'type_distribution' => $typeStats,
                'upload_path' => $this->uploadPath,
            ];

        } catch (\Exception $e) {
            log_message('error', 'Upload stats error: ' . $e->getMessage());
            return [];
        }
    }
}