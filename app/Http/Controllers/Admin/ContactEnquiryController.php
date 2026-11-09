<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactEnquiryRequest;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContactEnquiryController extends Controller
{
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:contact_enquiries,id'],
            'action' => ['required', Rule::in(['mark_read', 'mark_unread', 'delete'])],
        ]);

        $enquiries = ContactEnquiry::query()->whereKey($data['ids'])->get();
        if ($enquiries->count() !== count($data['ids'])) {
            throw ValidationException::withMessages([
                'ids' => 'One or more selected enquiries are unavailable.',
            ]);
        }

        $ability = $data['action'] === 'delete' ? 'delete' : 'update';
        foreach ($enquiries as $enquiry) {
            $this->authorize($ability, $enquiry);
        }

        DB::transaction(function () use ($data, $enquiries): void {
            if ($data['action'] === 'delete') {
                $enquiries->each(fn (ContactEnquiry $enquiry) => $enquiry->delete());

                return;
            }

            $isRead = $data['action'] === 'mark_read';
            $enquiries->each(fn (ContactEnquiry $enquiry) => $enquiry->update([
                'is_read' => $isRead,
            ]));
        });

        $message = match ($data['action']) {
            'mark_read' => 'marked as read.',
            'mark_unread' => 'marked as unread.',
            'delete' => 'deleted.',
            default => throw new \LogicException('Unsupported bulk enquiry action.'),
        };

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($enquiries).' enquiries '.$message,
        ]);

        return back();
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContactEnquiry::class);

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'unread', 'read'])],
        ]);
        $status = $data['status'] ?? 'all';

        $enquiries = ContactEnquiry::query()
            ->when($status === 'unread', fn ($query) => $query->where('is_read', false))
            ->when($status === 'read', fn ($query) => $query->where('is_read', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/enquiries/Index', [
            'enquiries' => $enquiries,
            'status' => $status,
            'unreadCount' => ContactEnquiry::query()->where('is_read', false)->count(),
            'canManage' => $request->user()->hasPermission('enquiries.manage'),
        ]);
    }

    public function update(UpdateContactEnquiryRequest $request, ContactEnquiry $enquiry): RedirectResponse
    {
        $this->authorize('update', $enquiry);

        $enquiry->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $enquiry->is_read ? 'Enquiry marked as read.' : 'Enquiry marked as unread.',
        ]);

        return back();
    }

    public function destroy(ContactEnquiry $enquiry): RedirectResponse
    {
        $this->authorize('delete', $enquiry);

        $enquiry->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Enquiry deleted.',
        ]);

        return back();
    }
}
