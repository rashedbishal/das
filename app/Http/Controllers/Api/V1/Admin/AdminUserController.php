<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\Admin\TransferMotherAdminRequest;
use App\Http\Requests\Admin\Admin\UpdateAdminRequest;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $admins) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyAdmins', User::class);

        return response()->json(
            User::query()->admins()->with('role')->orderBy('name')->paginate((int) $request->integer('per_page', 25))->toArray(),
        );
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->admins->createAdmin($request->user(), $request->validated());

        return response()->json(['data' => $admin->load('role')], 201);
    }

    public function show(User $admin): JsonResponse
    {
        $this->authorize('view', $admin);

        return response()->json(['data' => $admin->load('role')]);
    }

    public function update(UpdateAdminRequest $request, User $admin): JsonResponse
    {
        $updated = $this->admins->updateAdmin($request->user(), $admin, $request->validated());

        return response()->json(['data' => $updated->load('role')]);
    }

    public function destroy(Request $request, User $admin): JsonResponse
    {
        $this->authorize('delete', $admin);

        $this->admins->deleteAdmin($request->user(), $admin);

        return response()->json(null, 204);
    }

    public function deactivate(Request $request, User $admin): JsonResponse
    {
        $this->authorize('deactivate', $admin);

        $this->admins->deactivateAdmin($request->user(), $admin);

        return response()->json(['data' => $admin->fresh()]);
    }

    public function reactivate(Request $request, User $admin): JsonResponse
    {
        $this->authorize('update', $admin);

        $this->admins->reactivateAdmin($request->user(), $admin);

        return response()->json(['data' => $admin->fresh()]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'action' => ['required', Rule::in(['delete', 'deactivate', 'reactivate'])],
        ]);

        $admins = User::query()->admins()->whereKey($data['ids'])->get();
        abort_if($admins->count() !== count($data['ids']), 422, 'One or more selected accounts are not administrators.');

        foreach ($admins as $admin) {
            $this->authorize($data['action'] === 'delete' || $data['action'] === 'deactivate' ? $data['action'] : 'update', $admin);

            if ($request->user()->is($admin) || $admin->isMotherAdmin()) {
                abort(403, 'Your selection includes a protected administrator account.');
            }
        }

        foreach ($admins as $admin) {
            if ($data['action'] === 'delete') {
                $this->admins->deleteAdmin($request->user(), $admin);
            } elseif ($data['action'] === 'deactivate') {
                $this->admins->deactivateAdmin($request->user(), $admin);
            } elseif ($data['action'] === 'reactivate') {
                $this->admins->reactivateAdmin($request->user(), $admin);
            }
        }

        return response()->json(['message' => count($admins).' administrators updated.']);
    }

    public function transferMotherAdmin(TransferMotherAdminRequest $request, User $admin): JsonResponse
    {
        $promoted = $this->admins->transferMotherAdmin($request->user(), $admin, $request->string('password')->toString());

        return response()->json(['data' => $promoted->load('role')]);
    }
}
