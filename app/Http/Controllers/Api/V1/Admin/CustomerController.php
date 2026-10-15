<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
