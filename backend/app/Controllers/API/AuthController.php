<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Libraries\JWTAuth;
use App\Models\UserModel;
use App\Models\RoleModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class AuthController extends BaseController
{
    use ResponseTrait;

    private JWTAuth $jwtAuth;
    private UserModel $userModel;
    private RoleModel $roleModel;

    public function __construct()
    {
        $this->jwtAuth = new JWTAuth();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    /**
     * User registration
     */
    public function register(): ResponseInterface
    {
        try {
            $rules = [
                'username' => 'required|alpha_numeric_punct|min_length[3]|max_length[50]|is_unique[users.username]',
                'email' => 'required|valid_email|max_length[100]|is_unique[users.email]',
                'password' => 'required|min_length[8]|max_length[255]',
                'password_confirm' => 'required|matches[password]',
                'first_name' => 'permit_empty|alpha_space|max_length[50]',
                'last_name' => 'permit_empty|alpha_space|max_length[50]',
                'phone' => 'permit_empty|numeric|max_length[20]',
                'line_id' => 'permit_empty|max_length[50]',
                'discord_id' => 'permit_empty|max_length[50]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Validate password policy
            if (!$this->validatePasswordPolicy($data['password'])) {
                return $this->fail('Password does not meet security requirements', 400);
            }

            // Prepare user data
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'], // Will be hashed by model
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'line_id' => $data['line_id'] ?? null,
                'discord_id' => $data['discord_id'] ?? null,
                'status' => 'active',
                'email_verification_token' => bin2hex(random_bytes(32)),
            ];

            // Create user
            if (!$this->userModel->insert($userData)) {
                return $this->fail('Failed to create user account', 500);
            }

            $userId = $this->userModel->getInsertID();

            // Assign default user role
            $userRole = $this->roleModel->where('name', 'user')->first();
            if ($userRole) {
                $this->userModel->assignRole($userId, $userRole['id']);
            }

            // Get created user
            $user = $this->userModel->getUserWithRoles($userId);

            // Generate JWT tokens
            $tokens = $this->jwtAuth->generateToken($user);

            // Update login info
            $this->userModel->updateLoginInfo($userId, $this->request->getIPAddress());

            // Remove sensitive data
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'tokens' => $tokens,
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'Registration error: ' . $e->getMessage());
            return $this->fail('Registration failed', 500);
        }
    }

    /**
     * User login
     */
    public function login(): ResponseInterface
    {
        try {
            $rules = [
                'login' => 'required|min_length[3]|max_length[100]',
                'password' => 'required|min_length[1]|max_length[255]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $login = $data['login']; // Can be username or email
            $password = $data['password'];

            // Find user by username or email
            $user = $this->userModel->where('username', $login)
                                   ->orWhere('email', $login)
                                   ->where('deleted_at', null)
                                   ->first();

            if (!$user) {
                return $this->fail('Invalid credentials', 401);
            }

            // Check if user is locked
            if ($this->userModel->isLocked($user['id'])) {
                return $this->fail('Account is temporarily locked due to multiple failed login attempts', 423);
            }

            // Verify password
            if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
                // Increment failed attempts
                $this->userModel->incrementFailedAttempts($user['id']);
                return $this->fail('Invalid credentials', 401);
            }

            // Check user status
            if ($user['status'] !== 'active') {
                return $this->fail('Account is not active', 403);
            }

            // Get user with roles
            $userWithRoles = $this->userModel->getUserWithRoles($user['id']);

            // Generate JWT tokens
            $tokens = $this->jwtAuth->generateToken($userWithRoles);

            // Update login info
            $this->userModel->updateLoginInfo($user['id'], $this->request->getIPAddress());

            // Remove sensitive data
            unset($userWithRoles['password_hash'], $userWithRoles['password_reset_token'], $userWithRoles['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => $userWithRoles,
                    'tokens' => $tokens,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->fail('Login failed', 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): ResponseInterface
    {
        try {
            $rules = [
                'refresh_token' => 'required|min_length[10]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $refreshToken = $data['refresh_token'];

            // Refresh tokens
            $tokens = $this->jwtAuth->refreshToken($refreshToken);

            $response = [
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'data' => [
                    'tokens' => $tokens,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Token refresh error: ' . $e->getMessage());
            return $this->fail('Token refresh failed: ' . $e->getMessage(), 401);
        }
    }

    /**
     * User logout
     */
    public function logout(): ResponseInterface
    {
        try {
            $token = $this->jwtAuth->getTokenFromRequest($this->request);
            
            if ($token) {
                $this->jwtAuth->revokeToken($token);
            }

            $response = [
                'status' => 'success',
                'message' => 'Logout successful',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Logout error: ' . $e->getMessage());
            return $this->fail('Logout failed', 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(): ResponseInterface
    {
        try {
            $userPayload = $this->request->userPayload;
            $userId = $userPayload['user_id'];

            // Get fresh user data
            $user = $this->userModel->getUserWithRoles($userId);

            if (!$user) {
                return $this->fail('User not found', 404);
            }

            // Remove sensitive data
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Profile retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve profile', 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(): ResponseInterface
    {
        try {
            $userPayload = $this->request->userPayload;
            $userId = $userPayload['user_id'];

            $rules = [
                'first_name' => 'permit_empty|alpha_space|max_length[50]',
                'last_name' => 'permit_empty|alpha_space|max_length[50]',
                'phone' => 'permit_empty|numeric|max_length[20]',
                'line_id' => 'permit_empty|max_length[50]',
                'discord_id' => 'permit_empty|max_length[50]',
                'avatar' => 'permit_empty|max_length[255]',
                'preferences' => 'permit_empty|valid_json',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $updateData = [];
            $allowedFields = ['first_name', 'last_name', 'phone', 'line_id', 'discord_id', 'avatar'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Handle preferences
            if (isset($data['preferences'])) {
                $updateData['preferences'] = is_string($data['preferences']) 
                    ? $data['preferences'] 
                    : json_encode($data['preferences']);
            }

            // Update user
            if (!$this->userModel->update($userId, $updateData)) {
                return $this->fail('Failed to update profile', 500);
            }

            // Get updated user data
            $user = $this->userModel->getUserWithRoles($userId);
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Profile update error: ' . $e->getMessage());
            return $this->fail('Failed to update profile', 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(): ResponseInterface
    {
        try {
            $userPayload = $this->request->userPayload;
            $userId = $userPayload['user_id'];

            $rules = [
                'current_password' => 'required|min_length[1]',
                'new_password' => 'required|min_length[8]|max_length[255]',
                'new_password_confirm' => 'required|matches[new_password]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Get current user
            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->fail('User not found', 404);
            }

            // Verify current password
            if (!$this->userModel->verifyPassword($data['current_password'], $user['password_hash'])) {
                return $this->fail('Current password is incorrect', 400);
            }

            // Validate new password policy
            if (!$this->validatePasswordPolicy($data['new_password'])) {
                return $this->fail('New password does not meet security requirements', 400);
            }

            // Update password
            if (!$this->userModel->update($userId, ['password' => $data['new_password']])) {
                return $this->fail('Failed to update password', 500);
            }

            // Revoke all existing sessions except current one
            $currentToken = $this->jwtAuth->getTokenFromRequest($this->request);
            $this->jwtAuth->revokeAllUserTokens($userId);

            $response = [
                'status' => 'success',
                'message' => 'Password changed successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Password change error: ' . $e->getMessage());
            return $this->fail('Failed to change password', 500);
        }
    }

    /**
     * Validate password policy
     */
    private function validatePasswordPolicy(string $password): bool
    {
        $config = config('Security');
        $policy = $config->passwordPolicy;

        // Check minimum length
        if (strlen($password) < $policy['min_length']) {
            return false;
        }

        // Check maximum length
        if (strlen($password) > $policy['max_length']) {
            return false;
        }

        // Check for uppercase
        if ($policy['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for lowercase
        if ($policy['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for numbers
        if ($policy['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for symbols
        if ($policy['require_symbols'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }
}