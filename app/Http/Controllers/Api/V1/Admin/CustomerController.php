<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyCustomers', User::class);

        return response()->json(
            User::query()->customers()->orderBy('name')->paginate((int) $request->integer('per_page', 25))->toArray(),
        );
    }

    public function show(User $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return response()->json(['data' => $customer]);
    }

    public function deactivate(Request $request, User $customer): JsonResponse
    {
        $this->authorize('deactivate', $customer);

        $customer->update(['is_active' => false, 'updated_by' => $request->user()->id]);

        return response()->json(['data' => $customer->fresh()]);
    }

    public function reactivate(Request $request, User $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update(['is_active' => true, 'updated_by' => $request->user()->id]);

        return response()->json(['data' => $customer->fresh()]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'action' => ['required', Rule::in(['deactivate', 'reactivate'])],
        ]);

        $customers = User::query()->customers()->whereKey($data['ids'])->get();
        abort_if($customers->count() !== count($data['ids']), 422, 'One or more selected accounts are not customers.');

        foreach ($customers as $customer) {
            $this->authorize($data['action'] === 'deactivate' ? 'deactivate' : 'update', $customer);
        }

        $customers->each(fn (User $customer) => $customer->update([
            'is_active' => $data['action'] === 'reactivate',
            'updated_by' => $request->user()->id,
        ]));

        return response()->json(['message' => count($customers).' customers updated.']);
    }
}
