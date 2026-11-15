<script module lang="ts">
    import { index } from '@/routes/admin/activity-log';

    export const layout = {
        breadcrumbs: [{ title: 'Activity Log', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Eye from '@lucide/svelte/icons/eye';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { onDestroy, untrack } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import DatePicker from '@/components/dashboard/DatePicker.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button, buttonVariants } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Spinner } from '@/components/ui/spinner';
    import { destroy as clearLog, index as activityIndex } from '@/routes/admin/activity-log';

    type Change = { field: string; old: string | null; new: string | null };
    type Detail = { label: string; value: string };
    type ActivityRow = {
        id: number;
        category: string | null;
        event: string | null;
        description: string;
        causer: { name: string; type: string } | null;
        subject: { type: string; id: number | null } | null;
        ip_address: string | null;
        user_agent: string | null;
        changes: Change[];
        details: Detail[];
        created_at: string | null;
    };
    type Paginated<T> = {
        data: T[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    type Filters = { search: string; category: string; from: string; to: string };

    let {
        activities,
        filters,
        categories,
        canClear,
    }: {
        activities: Paginated<ActivityRow>;
        filters: Filters;
        categories: { value: string; label: string }[];
        canClear: boolean;
    } = $props();

    const categoryLabels = $derived(Object.fromEntries(categories.map((category) => [category.value, category.label])));
    const categoryOptions = $derived([{ value: 'all', label: 'All categories' }, ...categories]);

    // Filters apply on their own (search shortly after typing stops).
    let search = $state(untrack(() => filters.search));
    let category = $state(untrack(() => filters.category || 'all'));
    let from = $state(untrack(() => filters.from));
    let to = $state(untrack(() => filters.to));
    let loading = $state(false);
    let timer: ReturnType<typeof setTimeout> | undefined;

    const currentQuery = $derived.by(() => {
        const query: Record<string, string> = {};
        if (search.trim()) query.search = search.trim();
        if (category !== 'all') query.category = category;
        if (from) query.from = from;
        if (to) query.to = to;
        return query;
    });
    let appliedKey = untrack(() => JSON.stringify(currentQuery));

    $effect(() => {
        const query = currentQuery;
        const key = JSON.stringify(query);
        if (key === appliedKey) return;

        clearTimeout(timer);
        timer = setTimeout(() => {
            appliedKey = key;
            router.get(activityIndex.url({ query }), {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['activities', 'filters'],
                onStart: () => (loading = true),
                onFinish: () => (loading = false),
            });
        }, 250);
    });

    onDestroy(() => clearTimeout(timer));

    const hasFilters = $derived(Object.keys(currentQuery).length > 0);

    function resetFilters() {
        search = '';
        category = 'all';
        from = '';
        to = '';
    }

    let detailOpen = $state(false);
    let detail = $state<ActivityRow | null>(null);

    function showDetail(activity: ActivityRow) {
        detail = activity;
        detailOpen = true;
    }

    let clearOpen = $state(false);

    function confirmClear() {
        router.delete(clearLog.url(), { preserveScroll: true });
    }

    function formatDate(value: string | null): string {
        return value ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '';
    }

    function eventVariant(event: string | null): 'default' | 'secondary' | 'destructive' | 'outline' {
        if (event === null) return 'outline';
        if (event === 'deleted' || event === 'login_failed' || event === 'lockout' || event === 'cleared') return 'destructive';
        if (event === 'created' || event === 'login') return 'secondary';
        return 'outline';
    }

    function eventLabel(event: string | null): string {
        return event ? event.replaceAll('_', ' ') : '';
    }
</script>

<AppHead title="Activity Log" />

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Activity Log</h1>
            <p class="text-sm text-muted-foreground">
                Every change in the CMS, plus sign-ins and sign-outs. Entries older than 365 days are removed automatically.
            </p>
        </div>
        {#if canClear}
            <Button variant="destructive" disabled={activities.total === 0 && !hasFilters} onclick={() => (clearOpen = true)}>
                <Trash2 class="size-4" />
                Delete all logs
            </Button>
        {/if}
    </div>

    <div class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_10rem_10rem_auto]">
        <div class="relative">
            <Input
                type="search"
                bind:value={search}
                placeholder="Search action, person, email or IP..."
                aria-label="Search activity"
                class="pr-9"
            />
            {#if loading}
                <Spinner class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground" />
            {/if}
        </div>
        <FormSelect bind:value={category} options={categoryOptions} placeholder="All categories" />
        <DatePicker bind:value={from} placeholder="From date" max={to || undefined} />
        <DatePicker bind:value={to} placeholder="To date" min={from || undefined} />
        {#if hasFilters}
            <Button variant="ghost" onclick={resetFilters}>Reset</Button>
        {/if}
    </div>

    <p class="mb-2 text-sm text-muted-foreground">{activities.total.toLocaleString()} entries</p>

    <ResponsiveTable
        items={activities.data}
        key={(activity) => activity.id}
        columns={[
            { key: 'description', label: 'Activity' },
            { key: 'who', label: 'By' },
            { key: 'category', label: 'Category' },
            { key: 'when', label: 'When', class: 'whitespace-nowrap' },
        ]}
        empty={hasFilters ? 'No activity matches these filters.' : 'No activity recorded yet.'}
    >
        {#snippet cell(activity, column)}
            {#if column.key === 'description'}
                <div class="flex min-w-0 flex-col gap-1">
                    <span class="break-words">{activity.description}</span>
                    {#if activity.event}
                        <Badge variant={eventVariant(activity.event)} class="capitalize">{eventLabel(activity.event)}</Badge>
                    {/if}
                </div>
            {:else if column.key === 'who'}
                {#if activity.causer}
                    <span class="break-words">{activity.causer.name}</span>
                    <span class="block text-xs text-muted-foreground capitalize">{activity.causer.type}</span>
                {:else}
                    <span class="text-muted-foreground">Guest / system</span>
                {/if}
            {:else if column.key === 'category'}
                {categoryLabels[activity.category ?? ''] ?? activity.category ?? '—'}
            {:else}
                <time datetime={activity.created_at}>{formatDate(activity.created_at)}</time>
            {/if}
        {/snippet}
        {#snippet actions(activity)}
            <Button variant="ghost" size="sm" aria-label="View details" onclick={() => showDetail(activity)}>
                <Eye class="size-4" />
            </Button>
        {/snippet}
    </ResponsiveTable>

    {#if activities.last_page > 1}
        <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-sm">
            {#if activities.prev_page_url}
                <Link href={activities.prev_page_url} preserveScroll class={buttonVariants({ variant: 'ghost', size: 'sm' })}>Previous</Link>
            {/if}
            <span>Page {activities.current_page} of {activities.last_page}</span>
            {#if activities.next_page_url}
                <Link href={activities.next_page_url} preserveScroll class={buttonVariants({ variant: 'ghost', size: 'sm' })}>Next</Link>
            {/if}
        </div>
    {/if}
</div>

<Dialog bind:open={detailOpen}>
    <DialogContent class="max-h-[85vh] max-w-2xl overflow-y-auto">
        <DialogTitle>Activity details</DialogTitle>
        {#if detail}
            <div class="space-y-4 text-sm">
                <p class="font-medium break-words">{detail.description}</p>

                <dl class="grid grid-cols-[8rem_minmax(0,1fr)] gap-x-3 gap-y-1">
                    <dt class="text-muted-foreground">When</dt>
                    <dd>{formatDate(detail.created_at)}</dd>
                    <dt class="text-muted-foreground">By</dt>
                    <dd>{detail.causer ? `${detail.causer.name} (${detail.causer.type})` : 'Guest / system'}</dd>
                    <dt class="text-muted-foreground">Category</dt>
                    <dd>{categoryLabels[detail.category ?? ''] ?? detail.category ?? '—'}</dd>
                    {#if detail.subject}
                        <dt class="text-muted-foreground">Record</dt>
                        <dd class="capitalize">{detail.subject.type} #{detail.subject.id}</dd>
                    {/if}
                    <dt class="text-muted-foreground">IP address</dt>
                    <dd class="font-mono text-xs">{detail.ip_address ?? '—'}</dd>
                    <dt class="text-muted-foreground">Browser</dt>
                    <dd class="text-xs break-words text-muted-foreground">{detail.user_agent ?? '—'}</dd>
                    {#each detail.details as item (item.label)}
                        <dt class="text-muted-foreground">{item.label}</dt>
                        <dd class="break-words">{item.value}</dd>
                    {/each}
                </dl>

                {#if detail.changes.length > 0}
                    <div class="overflow-x-auto rounded-md border">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Field</th>
                                    <th class="px-3 py-2 font-medium">Before</th>
                                    <th class="px-3 py-2 font-medium">After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                {#each detail.changes as change (change.field)}
                                    <tr class="align-top">
                                        <td class="px-3 py-2 font-medium">{change.field}</td>
                                        <td class="px-3 py-2 break-words text-muted-foreground">{change.old ?? ''}</td>
                                        <td class="px-3 py-2 break-words">{change.new ?? ''}</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                {/if}
            </div>
        {/if}
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={clearOpen}
    title="Delete all activity logs?"
    description="This permanently deletes every entry in the activity log, not just the ones currently shown. A single entry recording that you cleared the log will remain. This cannot be undone."
    confirmLabel="Delete all logs"
    onConfirm={confirmClear}
/>
