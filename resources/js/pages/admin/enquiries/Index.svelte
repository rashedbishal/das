<script module lang="ts">
    import { index } from '@/routes/admin/enquiries';

    export const layout = {
        breadcrumbs: [{ title: 'Enquiries', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Eye from '@lucide/svelte/icons/eye';
    import Mail from '@lucide/svelte/icons/mail';
    import MailOpen from '@lucide/svelte/icons/mail-open';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import {
        bulk as bulkEnquiries,
        destroy,
        index as enquiriesIndex,
        update,
    } from '@/routes/admin/enquiries';

    type Enquiry = {
        id: number;
        name: string;
        business_name: string | null;
        phone: string;
        email: string | null;
        product_name: string | null;
        message: string | null;
        is_read: boolean;
        created_at: string;
    };

    type PaginationLink = { url: string | null; label: string; active: boolean };
    type Paginated<T> = {
        data: T[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };

    let {
        enquiries,
        status,
        unreadCount,
        canManage,
    }: {
        enquiries: Paginated<Enquiry>;
        status: 'all' | 'unread' | 'read';
        unreadCount: number;
        canManage: boolean;
    } = $props();

    let selectedEnquiry = $state<Enquiry | null>(null);
    let deleteTarget = $state<Enquiry | null>(null);
    let deleteOpen = $state(false);
    let detailOpen = $state(false);
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

    const columns = [
        { key: 'name', label: 'From' },
        { key: 'contact', label: 'Contact' },
        { key: 'product', label: 'Product' },
        { key: 'message', label: 'Message' },
        { key: 'status', label: 'Status' },
        { key: 'received', label: 'Received', class: 'whitespace-nowrap' },
    ];

    function receivedAt(value: string): string {
        return new Intl.DateTimeFormat('en-BD', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: 'Asia/Dhaka',
        }).format(new Date(value));
    }

    function toggleRead(enquiry: Enquiry) {
        router.patch(
            update.url(enquiry.id),
            { is_read: !enquiry.is_read },
            {
                onSuccess: () => {
                    if (selectedEnquiry?.id === enquiry.id) {
                        selectedEnquiry = {
                            ...selectedEnquiry,
                            is_read: !enquiry.is_read,
                        };
                    }
                },
            },
        );
    }

    function askDelete(enquiry: Enquiry) {
        deleteTarget = enquiry;
        deleteOpen = true;
    }

    function deleteEnquiry() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id));
            if (selectedEnquiry?.id === deleteTarget.id) {
                detailOpen = false;
                selectedEnquiry = null;
            }
        }
    }

    function runBulkAction(action: 'mark_read' | 'mark_unread' | 'delete') {
        router.post(
            bulkEnquiries.url(),
            { ids: selectedIds, action },
            {
                onSuccess: () => {
                    selectedIds = [];
                    bulkError = null;
                    bulkDeleteOpen = false;
                },
                onError: (errors) => {
                    bulkError = Object.values(errors).join(' ');
                    bulkDeleteOpen = false;
                },
            },
        );
    }
</script>

<AppHead title="Contact enquiries" />

<div class="w-full p-4">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Contact enquiries</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {unreadCount} unread {unreadCount === 1 ? 'enquiry' : 'enquiries'}
            </p>
        </div>
    </div>

    <nav aria-label="Enquiry status" class="mb-4 flex flex-wrap gap-2">
        {#each [
            { value: 'all', label: 'All' },
            { value: 'unread', label: 'Unread' },
            { value: 'read', label: 'Read' },
        ] as filter (filter.value)}
            <Link
                href={filter.value === 'all'
                    ? enquiriesIndex().url
                    : `${enquiriesIndex().url}?status=${filter.value}`}
                class="rounded-md border px-3 py-1.5 text-sm {status === filter.value
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-background text-muted-foreground hover:text-foreground'}"
                aria-current={status === filter.value ? 'page' : undefined}
            >
                {filter.label}
            </Link>
        {/each}
    </nav>

    {#if canManage}
        <BulkActions
            count={selectedIds.length}
            error={bulkError}
            onClear={() => {
                selectedIds = [];
                bulkError = null;
            }}
        >
            {#snippet children()}
                <Button
                    size="sm"
                    variant="outline"
                    onclick={() => runBulkAction('mark_read')}
                >
                    Mark read
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    onclick={() => runBulkAction('mark_unread')}
                >
                    Mark unread
                </Button>
                <Button
                    size="sm"
                    variant="destructive"
                    onclick={() => (bulkDeleteOpen = true)}
                >
                    Delete
                </Button>
            {/snippet}
        </BulkActions>
    {/if}

    <ResponsiveTable
        items={enquiries.data}
        key={(enquiry) => enquiry.id}
        {columns}
        empty="No enquiries in this view."
        selectedKeys={canManage ? selectedIds : undefined}
        onSelectionChange={canManage
            ? (keys) => (selectedIds = keys.map(Number))
            : undefined}
    >
        {#snippet cell(enquiry, column)}
            {#if column.key === 'name'}
                <div class="font-medium">{enquiry.name}</div>
                {#if enquiry.business_name}
                    <div class="text-xs text-muted-foreground">{enquiry.business_name}</div>
                {/if}
            {:else if column.key === 'contact'}
                <div>{enquiry.phone}</div>
                {#if enquiry.email}
                    <div class="break-all text-xs text-muted-foreground">{enquiry.email}</div>
                {/if}
            {:else if column.key === 'product'}
                {enquiry.product_name ?? 'General enquiry'}
            {:else if column.key === 'message'}
                <button
                    type="button"
                    class="max-w-64 text-left text-muted-foreground hover:text-foreground"
                    onclick={() => {
                        selectedEnquiry = enquiry;
                        detailOpen = true;
                    }}
                >
                    {enquiry.message || 'No message provided'}
                </button>
            {:else if column.key === 'status'}
                <Badge variant={enquiry.is_read ? 'outline' : 'default'}>
                    {enquiry.is_read ? 'Read' : 'Unread'}
                </Badge>
            {:else}
                {receivedAt(enquiry.created_at)}
            {/if}
        {/snippet}
        {#snippet actions(enquiry)}
            <Button
                variant="ghost"
                size="sm"
                aria-label="View enquiry from {enquiry.name}"
                onclick={() => {
                    selectedEnquiry = enquiry;
                    detailOpen = true;
                }}
            >
                <Eye class="size-4" />
            </Button>
            {#if canManage}
                <Button
                    variant="ghost"
                    size="sm"
                    aria-label="Mark enquiry as {enquiry.is_read ? 'unread' : 'read'}"
                    onclick={() => toggleRead(enquiry)}
                >
                    {#if enquiry.is_read}
                        <Mail class="size-4" />
                    {:else}
                        <MailOpen class="size-4" />
                    {/if}
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    aria-label="Delete enquiry from {enquiry.name}"
                    onclick={() => askDelete(enquiry)}
                >
                    <Trash2 class="size-4" />
                </Button>
            {/if}
        {/snippet}
    </ResponsiveTable>

    {#if enquiries.last_page > 1}
        <nav
            aria-label="Pagination"
            class="mt-4 flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {enquiries.current_page} of {enquiries.last_page} · {enquiries.total} total
            </p>
            <div class="flex flex-wrap gap-1">
                {#each enquiries.links as link, i (`${i}-${link.label}`)}
                    {#if link.url}
                        <Link
                            href={link.url}
                            class="rounded-md border px-3 py-1.5 text-sm {link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted'}"
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {link.label
                                .replace('&laquo; Previous', 'Previous')
                                .replace('Next &raquo;', 'Next')}
                        </Link>
                    {/if}
                {/each}
            </div>
        </nav>
    {/if}
</div>

<Dialog bind:open={detailOpen}>
    <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
        <DialogTitle>Enquiry from {selectedEnquiry?.name ?? ''}</DialogTitle>
        <DialogDescription>
            Received {selectedEnquiry ? receivedAt(selectedEnquiry.created_at) : ''}
        </DialogDescription>
        {#if selectedEnquiry}
            <dl class="grid gap-3 text-sm sm:grid-cols-[8rem_1fr]">
                <dt class="text-muted-foreground">Clinic / business</dt>
                <dd>{selectedEnquiry.business_name ?? '—'}</dd>
                <dt class="text-muted-foreground">Phone</dt>
                <dd><a class="text-primary" href="tel:{selectedEnquiry.phone}">{selectedEnquiry.phone}</a></dd>
                <dt class="text-muted-foreground">Email</dt>
                <dd>
                    {#if selectedEnquiry.email}
                        <a class="break-all text-primary" href="mailto:{selectedEnquiry.email}">{selectedEnquiry.email}</a>
                    {:else}
                        —
                    {/if}
                </dd>
                <dt class="text-muted-foreground">Product</dt>
                <dd>{selectedEnquiry.product_name ?? 'General enquiry'}</dd>
                <dt class="text-muted-foreground">Message</dt>
                <dd class="whitespace-pre-wrap break-words">{selectedEnquiry.message || 'No message provided'}</dd>
            </dl>
        {/if}
        <DialogFooter>
            {#if canManage && selectedEnquiry}
                <Button
                    type="button"
                    variant="outline"
                    onclick={() => {
                        if (selectedEnquiry) {
                            toggleRead(selectedEnquiry);
                        }
                    }}
                >
                    Mark as {selectedEnquiry.is_read ? 'unread' : 'read'}
                </Button>
            {/if}
            <Button type="button" onclick={() => (detailOpen = false)}>Close</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteOpen}
    title="Delete enquiry?"
    description={deleteTarget
        ? `Permanently delete the enquiry from ${deleteTarget.name}? This cannot be undone.`
        : ''}
    onConfirm={deleteEnquiry}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected enquiries?"
    description={`Permanently delete ${selectedIds.length} selected enquiries? This cannot be undone.`}
    onConfirm={() => runBulkAction('delete')}
/>
