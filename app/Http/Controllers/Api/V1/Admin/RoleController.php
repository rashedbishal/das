<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json(['data' => Role::query()->with('permissions')->orderBy('name')->get()]);
    }

    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json(['data' => Permission::query()->orderBy('group')->orderBy('name')->get()]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roles->create($request->user(), $request->validated(), $request->array('permissions'));

        return response()->json(['data' => $role->load('permissions')], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return response()->json(['data' => $role->load('permissions')]);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $updated = $this->roles->update($request->user(), $role, $request->validated(), $request->array('permissions'));

        return response()->json(['data' => $updated->load('permissions')]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roles->delete($role);

        return response()->json(status: 204);
    }
}
