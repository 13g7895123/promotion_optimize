<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class RoleController extends BaseController
{
    use ResponseTrait;

    private RoleModel $roleModel;
    private RolePermissionModel $rolePermissionModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->rolePermissionModel = new RolePermissionModel();
    }

    /**
     * Get all roles
     */
    public function index(): ResponseInterface
    {
        try {
            $includePermissions = $this->request->getGet('include_permissions') === 'true';
            $level = $this->request->getGet('level');

            if ($level) {
                $roles = $this->roleModel->getRolesByLevel((int) $level);
            } else {
                $roles = $this->roleModel->where('is_active', true)
                                        ->orderBy('level', 'ASC')
                                        ->findAll();
            }

            // Include permissions if requested
            if ($includePermissions) {
                foreach ($roles as &$role) {
                    $role['permissions'] = $this->roleModel->getRolePermissions($role['id']);
                }
            }

            $response = [
                'status' => 'success',
                'message' => 'Roles retrieved successfully',
                'data' => [
                    'roles' => $roles,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Roles retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve roles', 500);
        }
    }

    /**
     * Get single role by ID
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $role = $this->roleModel->getRoleWithPermissions($id);

            if (!$role) {
                return $this->failNotFound('Role not found');
            }

            // Get role users
            $role['users'] = $this->roleModel->getRoleUsers($id);

            // Get role statistics
            $role['stats'] = $this->roleModel->getRoleStats($id);

            $response = [
                'status' => 'success',
                'message' => 'Role retrieved successfully',
                'data' => [
                    'role' => $role,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Role retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve role', 500);
        }
    }

    /**
     * Create new role
     */
    public function create(): ResponseInterface
    {
        try {
            $rules = [
                'name' => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.name]',
                'display_name' => 'required|min_length[3]|max_length[100]',
                'description' => 'permit_empty|max_length[500]',
                'level' => 'required|integer|greater_than[0]|less_than[6]',
                'permissions' => 'permit_empty|is_array',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Check if level already exists
            $existingRole = $this->roleModel->where('level', $data['level'])->first();
            if ($existingRole) {
                return $this->fail('Role with this level already exists', 400);
            }

            // Prepare role data
            $roleData = [
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
                'level' => $data['level'],
                'is_active' => true,
            ];

            // Create role
            if (!$this->roleModel->insert($roleData)) {
                return $this->fail('Failed to create role', 500);
            }

            $roleId = $this->roleModel->getInsertID();

            // Assign permissions if provided
            if (!empty($data['permissions'])) {
                $this->roleModel->syncPermissions($roleId, $data['permissions']);
            }

            // Get created role with permissions
            $role = $this->roleModel->getRoleWithPermissions($roleId);

            $response = [
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => [
                    'role' => $role,
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'Role creation error: ' . $e->getMessage());
            return $this->fail('Failed to create role', 500);
        }
    }

    /**
     * Update role
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $role = $this->roleModel->find($id);
            if (!$role) {
                return $this->failNotFound('Role not found');
            }

            // Don't allow updating system roles
            $systemRoles = ['super_admin', 'admin', 'user'];
            if (in_array($role['name'], $systemRoles)) {
                return $this->fail('Cannot modify system roles', 400);
            }

            $rules = [
                'name' => "permit_empty|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.name,id,{$id}]",
                'display_name' => 'permit_empty|min_length[3]|max_length[100]',
                'description' => 'permit_empty|max_length[500]',
                'level' => 'permit_empty|integer|greater_than[0]|less_than[6]',
                'is_active' => 'permit_empty|in_list[0,1]',
                'permissions' => 'permit_empty|is_array',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Check if level already exists (if updating level)
            if (isset($data['level']) && $data['level'] !== $role['level']) {
                $existingRole = $this->roleModel->where('level', $data['level'])
                                                ->where('id !=', $id)
                                                ->first();
                if ($existingRole) {
                    return $this->fail('Role with this level already exists', 400);
                }
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = ['name', 'display_name', 'description', 'level', 'is_active'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Update role
            if (!$this->roleModel->update($id, $updateData)) {
                return $this->fail('Failed to update role', 500);
            }

            // Update permissions if provided
            if (isset($data['permissions'])) {
                $this->roleModel->syncPermissions($id, $data['permissions']);
            }

            // Get updated role with permissions
            $role = $this->roleModel->getRoleWithPermissions($id);

            $response = [
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => [
                    'role' => $role,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Role update error: ' . $e->getMessage());
            return $this->fail('Failed to update role', 500);
        }
    }

    /**
     * Delete role
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $role = $this->roleModel->find($id);
            if (!$role) {
                return $this->failNotFound('Role not found');
            }

            // Check if role can be deleted
            if (!$this->roleModel->canDelete($id)) {
                return $this->fail('Cannot delete this role. It may be a system role or have active users', 400);
            }

            // Delete role (this will cascade delete role_permissions due to foreign key)
            if (!$this->roleModel->delete($id)) {
                return $this->fail('Failed to delete role', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Role deleted successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Role deletion error: ' . $e->getMessage());
            return $this->fail('Failed to delete role', 500);
        }
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(int $roleId): ResponseInterface
    {
        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                return $this->failNotFound('Role not found');
            }

            $rules = [
                'permission_id' => 'required|integer|is_not_unique[permissions.id]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $permissionId = $data['permission_id'];

            // Check if already assigned
            if ($this->roleModel->hasPermission($roleId, $permissionId)) {
                return $this->fail('Role already has this permission', 400);
            }

            // Assign permission
            if (!$this->roleModel->assignPermission($roleId, $permissionId)) {
                return $this->fail('Failed to assign permission', 500);
            }

            // Get updated role with permissions
            $role = $this->roleModel->getRoleWithPermissions($roleId);

            $response = [
                'status' => 'success',
                'message' => 'Permission assigned successfully',
                'data' => [
                    'role' => $role,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Permission assignment error: ' . $e->getMessage());
            return $this->fail('Failed to assign permission', 500);
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, int $permissionId): ResponseInterface
    {
        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                return $this->failNotFound('Role not found');
            }

            // Check if role has this permission
            if (!$this->roleModel->hasPermission($roleId, $permissionId)) {
                return $this->fail('Role does not have this permission', 400);
            }

            // Remove permission
            if (!$this->roleModel->removePermission($roleId, $permissionId)) {
                return $this->fail('Failed to remove permission', 500);
            }

            // Get updated role with permissions
            $role = $this->roleModel->getRoleWithPermissions($roleId);

            $response = [
                'status' => 'success',
                'message' => 'Permission removed successfully',
                'data' => [
                    'role' => $role,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Permission removal error: ' . $e->getMessage());
            return $this->fail('Failed to remove permission', 500);
        }
    }
}