<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\PermissionModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class PermissionController extends BaseController
{
    use ResponseTrait;

    private PermissionModel $permissionModel;

    public function __construct()
    {
        $this->permissionModel = new PermissionModel();
    }

    /**
     * Get all permissions
     */
    public function index(): ResponseInterface
    {
        try {
            $groupByResource = $this->request->getGet('group_by_resource') === 'true';
            $resource = $this->request->getGet('resource');
            $search = $this->request->getGet('search');

            if ($search) {
                $permissions = $this->permissionModel->searchPermissions($search, [
                    'resource' => $resource,
                ]);
            } elseif ($resource) {
                $permissions = $this->permissionModel->getResourcePermissions($resource);
            } elseif ($groupByResource) {
                $permissions = $this->permissionModel->getPermissionsByResource();
            } else {
                $permissions = $this->permissionModel->where('is_active', true)
                                                   ->orderBy('resource, action')
                                                   ->findAll();
            }

            $response = [
                'status' => 'success',
                'message' => 'Permissions retrieved successfully',
                'data' => [
                    'permissions' => $permissions,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Permissions retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve permissions', 500);
        }
    }

    /**
     * Get single permission by ID
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $permission = $this->permissionModel->find($id);

            if (!$permission) {
                return $this->failNotFound('Permission not found');
            }

            // Get roles that have this permission
            $permission['roles'] = $this->permissionModel->getPermissionRoles($id);

            // Get users that have this permission
            $permission['users'] = $this->permissionModel->getPermissionUsers($id);

            // Get permission statistics
            $permission['stats'] = $this->permissionModel->getPermissionStats($id);

            $response = [
                'status' => 'success',
                'message' => 'Permission retrieved successfully',
                'data' => [
                    'permission' => $permission,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Permission retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve permission', 500);
        }
    }

    /**
     * Create new permission
     */
    public function create(): ResponseInterface
    {
        try {
            $rules = [
                'display_name' => 'required|min_length[3]|max_length[150]',
                'description' => 'permit_empty|max_length[500]',
                'resource' => 'required|alpha_dash|min_length[3]|max_length[50]',
                'action' => 'required|alpha_dash|min_length[3]|max_length[20]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Check if permission already exists
            if ($this->permissionModel->permissionExists($data['resource'], $data['action'])) {
                return $this->fail('Permission already exists for this resource and action', 400);
            }

            // Prepare permission data
            $permissionData = [
                'name' => $data['resource'] . '.' . $data['action'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
                'resource' => $data['resource'],
                'action' => $data['action'],
                'is_active' => true,
            ];

            // Create permission
            if (!$this->permissionModel->insert($permissionData)) {
                return $this->fail('Failed to create permission', 500);
            }

            $permissionId = $this->permissionModel->getInsertID();
            $permission = $this->permissionModel->find($permissionId);

            $response = [
                'status' => 'success',
                'message' => 'Permission created successfully',
                'data' => [
                    'permission' => $permission,
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'Permission creation error: ' . $e->getMessage());
            return $this->fail('Failed to create permission', 500);
        }
    }

    /**
     * Update permission
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $permission = $this->permissionModel->find($id);
            if (!$permission) {
                return $this->failNotFound('Permission not found');
            }

            // Don't allow updating core system permissions
            $corePermissions = [
                'users.read', 'users.update', 'servers.read', 'servers.create',
                'promotions.read', 'promotions.participate', 'system.health'
            ];

            if (in_array($permission['name'], $corePermissions)) {
                return $this->fail('Cannot modify core system permissions', 400);
            }

            $rules = [
                'display_name' => 'permit_empty|min_length[3]|max_length[150]',
                'description' => 'permit_empty|max_length[500]',
                'resource' => 'permit_empty|alpha_dash|min_length[3]|max_length[50]',
                'action' => 'permit_empty|alpha_dash|min_length[3]|max_length[20]',
                'is_active' => 'permit_empty|in_list[0,1]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Check if resource/action combination already exists (if updating those fields)
            if ((isset($data['resource']) && $data['resource'] !== $permission['resource']) ||
                (isset($data['action']) && $data['action'] !== $permission['action'])) {
                
                $newResource = $data['resource'] ?? $permission['resource'];
                $newAction = $data['action'] ?? $permission['action'];
                
                $existing = $this->permissionModel->where('resource', $newResource)
                                                  ->where('action', $newAction)
                                                  ->where('id !=', $id)
                                                  ->first();
                
                if ($existing) {
                    return $this->fail('Permission already exists for this resource and action', 400);
                }
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = ['display_name', 'description', 'resource', 'action', 'is_active'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Update name if resource or action changed
            if (isset($data['resource']) || isset($data['action'])) {
                $newResource = $data['resource'] ?? $permission['resource'];
                $newAction = $data['action'] ?? $permission['action'];
                $updateData['name'] = $newResource . '.' . $newAction;
            }

            // Update permission
            if (!$this->permissionModel->update($id, $updateData)) {
                return $this->fail('Failed to update permission', 500);
            }

            // Get updated permission
            $permission = $this->permissionModel->find($id);

            $response = [
                'status' => 'success',
                'message' => 'Permission updated successfully',
                'data' => [
                    'permission' => $permission,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Permission update error: ' . $e->getMessage());
            return $this->fail('Failed to update permission', 500);
        }
    }

    /**
     * Delete permission
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $permission = $this->permissionModel->find($id);
            if (!$permission) {
                return $this->failNotFound('Permission not found');
            }

            // Check if permission can be deleted
            if (!$this->permissionModel->canDelete($id)) {
                return $this->fail('Cannot delete this permission. It may be a core permission or assigned to roles', 400);
            }

            // Delete permission (this will cascade delete role_permissions due to foreign key)
            if (!$this->permissionModel->delete($id)) {
                return $this->fail('Failed to delete permission', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Permission deleted successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Permission deletion error: ' . $e->getMessage());
            return $this->fail('Failed to delete permission', 500);
        }
    }
}