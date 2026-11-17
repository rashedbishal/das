<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyCustomers', User::class);

        return response()->json(
            User::query()
                ->customers()
                ->search($request->string('search')->toString())
                ->newestFirst()
                ->paginate(max(1, min($request->integer('per_page', 25), 100)))
                ->withQueryString()
                ->toArray(),
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

    /**
     * Permanently delete a customer account.
     */
    public function destroy(User $customer): JsonResponse
    {
        abort_unless($customer->isCustomer(), 404);
        $this->authorize('delete', $customer);

        $this->customers->delete($customer);

        return response()->json(status: 204);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'action' => ['required', Rule::in(['deactivate', 'reactivate', 'delete'])],
        ]);

        $customers = User::query()->customers()->whereKey($data['ids'])->get();
        abort_if($customers->count() !== count($data['ids']), 422, 'One or more selected accounts are not customers.');

        foreach ($customers as $customer) {
            $this->authorize(match ($data['action']) {
                'deactivate' => 'deactivate',
                'delete' => 'delete',
                default => 'update',
            }, $customer);
        }

        if ($data['action'] === 'delete') {
            $customers->each(fn (User $customer) => $this->customers->delete($customer));

            return response()->json(['message' => count($customers).' '.(count($customers) === 1 ? 'customer' : 'customers').' deleted.']);
        }

        $customers->each(fn (User $customer) => $customer->update([
            'is_active' => $data['action'] === 'reactivate',
            'updated_by' => $request->user()->id,
        ]));

        return response()->json(['message' => count($customers).' customers updated.']);
    }
}
