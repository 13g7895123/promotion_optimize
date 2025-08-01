<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\UserRoleModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class UserController extends BaseController
{
    use ResponseTrait;

    private UserModel $userModel;
    private RoleModel $roleModel;
    private UserRoleModel $userRoleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->userRoleModel = new UserRoleModel();
    }

    /**
     * Get users list with pagination and filters
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            
            $filters = [
                'status' => $this->request->getGet('status'),
                'role' => $this->request->getGet('role'),
                'search' => $this->request->getGet('search'),
            ];

            $result = $this->userModel->getUsers($filters, $page, $perPage);

            // Remove sensitive data from users
            foreach ($result['users'] as &$user) {
                unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);
            }

            $response = [
                'status' => 'success',
                'message' => 'Users retrieved successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Users retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve users', 500);
        }
    }

    /**
     * Get single user by ID
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $user = $this->userModel->getUserWithRoles($id);

            if (!$user) {
                return $this->failNotFound('User not found');
            }

            // Remove sensitive data
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'User retrieved successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'User retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve user', 500);
        }
    }

    /**
     * Create new user
     */
    public function create(): ResponseInterface
    {
        try {
            $rules = [
                'username' => 'required|alpha_numeric_punct|min_length[3]|max_length[50]|is_unique[users.username]',
                'email' => 'required|valid_email|max_length[100]|is_unique[users.email]',
                'password' => 'required|min_length[8]|max_length[255]',
                'first_name' => 'permit_empty|alpha_space|max_length[50]',
                'last_name' => 'permit_empty|alpha_space|max_length[50]',
                'phone' => 'permit_empty|numeric|max_length[20]',
                'line_id' => 'permit_empty|max_length[50]',
                'discord_id' => 'permit_empty|max_length[50]',
                'status' => 'permit_empty|in_list[active,inactive,suspended,pending]',
                'roles' => 'permit_empty|is_array',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

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
                'status' => $data['status'] ?? 'active',
            ];

            // Create user
            if (!$this->userModel->insert($userData)) {
                return $this->fail('Failed to create user', 500);
            }

            $userId = $this->userModel->getInsertID();
            $currentUserId = $this->request->userPayload['user_id'];

            // Assign roles if provided
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $roleId) {
                    $this->userModel->assignRole($userId, $roleId, $currentUserId);
                }
            } else {
                // Assign default user role
                $userRole = $this->roleModel->where('name', 'user')->first();
                if ($userRole) {
                    $this->userModel->assignRole($userId, $userRole['id'], $currentUserId);
                }
            }

            // Get created user with roles
            $user = $this->userModel->getUserWithRoles($userId);
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'User creation error: ' . $e->getMessage());
            return $this->fail('Failed to create user', 500);
        }
    }

    /**
     * Update user
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $user = $this->userModel->find($id);
            if (!$user) {
                return $this->failNotFound('User not found');
            }

            $rules = [
                'username' => "permit_empty|alpha_numeric_punct|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
                'email' => "permit_empty|valid_email|max_length[100]|is_unique[users.email,id,{$id}]",
                'first_name' => 'permit_empty|alpha_space|max_length[50]',
                'last_name' => 'permit_empty|alpha_space|max_length[50]',
                'phone' => 'permit_empty|numeric|max_length[20]',
                'line_id' => 'permit_empty|max_length[50]',
                'discord_id' => 'permit_empty|max_length[50]',
                'status' => 'permit_empty|in_list[active,inactive,suspended,pending]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $updateData = [];
            $allowedFields = ['username', 'email', 'first_name', 'last_name', 'phone', 'line_id', 'discord_id', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Update user
            if (!$this->userModel->update($id, $updateData)) {
                return $this->fail('Failed to update user', 500);
            }

            // Get updated user with roles
            $user = $this->userModel->getUserWithRoles($id);
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'User update error: ' . $e->getMessage());
            return $this->fail('Failed to update user', 500);
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $user = $this->userModel->find($id);
            if (!$user) {
                return $this->failNotFound('User not found');
            }

            // Check if trying to delete self
            $currentUserId = $this->request->userPayload['user_id'];
            if ($id === $currentUserId) {
                return $this->fail('Cannot delete your own account', 400);
            }

            // Soft delete user
            if (!$this->userModel->delete($id)) {
                return $this->fail('Failed to delete user', 500);
            }

            // Deactivate user roles
            $this->userRoleModel->where('user_id', $id)
                               ->set(['is_active' => false])
                               ->update();

            $response = [
                'status' => 'success',
                'message' => 'User deleted successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'User deletion error: ' . $e->getMessage());
            return $this->fail('Failed to delete user', 500);
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId): ResponseInterface
    {
        try {
            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->failNotFound('User not found');
            }

            $rules = [
                'role_id' => 'required|integer|is_not_unique[roles.id]',
                'expires_at' => 'permit_empty|valid_date',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $roleId = $data['role_id'];
            $expiresAt = $data['expires_at'] ?? null;
            $assignedBy = $this->request->userPayload['user_id'];

            // Check if role exists
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                return $this->fail('Role not found', 404);
            }

            // Check if user already has this role
            if ($this->userRoleModel->hasActiveRole($userId, $roleId)) {
                return $this->fail('User already has this role', 400);
            }

            // Assign role
            if (!$this->userModel->assignRole($userId, $roleId, $assignedBy, $expiresAt)) {
                return $this->fail('Failed to assign role', 500);
            }

            // Get updated user with roles
            $user = $this->userModel->getUserWithRoles($userId);
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'Role assigned successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Role assignment error: ' . $e->getMessage());
            return $this->fail('Failed to assign role', 500);
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId, int $roleId): ResponseInterface
    {
        try {
            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->failNotFound('User not found');
            }

            $role = $this->roleModel->find($roleId);
            if (!$role) {
                return $this->fail('Role not found', 404);
            }

            // Check if user has this role
            if (!$this->userRoleModel->hasActiveRole($userId, $roleId)) {
                return $this->fail('User does not have this role', 400);
            }

            // Remove role
            if (!$this->userModel->removeRole($userId, $roleId)) {
                return $this->fail('Failed to remove role', 500);
            }

            // Get updated user with roles
            $user = $this->userModel->getUserWithRoles($userId);
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);

            $response = [
                'status' => 'success',
                'message' => 'Role removed successfully',
                'data' => [
                    'user' => $user,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Role removal error: ' . $e->getMessage());
            return $this->fail('Failed to remove role', 500);
        }
    }
}