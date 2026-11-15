<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import Building2 from '@lucide/svelte/icons/building-2';
    import ClipboardList from '@lucide/svelte/icons/clipboard-list';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Eye from '@lucide/svelte/icons/eye';
    import Mail from '@lucide/svelte/icons/mail';
    import Package from '@lucide/svelte/icons/package';
    import PackageSearch from '@lucide/svelte/icons/package-search';
    import Settings from '@lucide/svelte/icons/settings';
    import Tags from '@lucide/svelte/icons/tags';
    import UserCog from '@lucide/svelte/icons/user-cog';
    import Users from '@lucide/svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import BarChart from '@/components/dashboard/BarChart.svelte';
    import type { Column } from '@/components/dashboard/ResponsiveTable.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import StatCard from '@/components/dashboard/StatCard.svelte';
    import TrendChart from '@/components/dashboard/TrendChart.svelte';
    import { buttonVariants } from '@/components/ui/button';
    import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { index as activityLogIndex } from '@/routes/admin/activity-log';
    import { index as adminBrands } from '@/routes/admin/brands';
    import { index as adminCategories } from '@/routes/admin/categories';
    import { index as adminEnquiries } from '@/routes/admin/enquiries';
    import { index as adminProducts } from '@/routes/admin/products';
    import { edit as adminSettings } from '@/routes/admin/settings';
    import { index as adminTags } from '@/routes/admin/tags';
    import { index as adminUsers } from '@/routes/admin/users';

    type LowStockProduct = {
        id: number;
        name: string;
        sku: string;
        stock_quantity: number;
        stock_status: string;
    };

    type ActivityEntry = {
        id: number;
        description: string | null;
        action: string;
        created_at: string;
        user: { name: string } | null;
    };

    let {
        permissions,
        pageViews,
        stats,
        categoryBreakdown,
        monthlyTrend,
        lowStock,
        recentActivity,
    }: {
        permissions?: Record<string, boolean>;
        pageViews?: {
            today: number;
            lastSevenDays: number;
            trend: { label: string; value: number }[];
        };
        stats?: {
            products: { total: number; published: number; draft: number } | null;
            categories: { total: number } | null;
            subcategories: { total: number } | null;
            brands: { total: number } | null;
            customers: { total: number } | null;
            admins: { total: number } | null;
        };
        categoryBreakdown?: { label: string; value: number }[] | null;
        monthlyTrend?: { label: string; value: number }[] | null;
        lowStock?: LowStockProduct[] | null;
        recentActivity?: ActivityEntry[] | null;
    } = $props();

    const user = $derived(page.props.auth.user);

    const lowStockColumns: Column[] = [
        { key: 'name', label: 'Product' },
        { key: 'sku', label: 'SKU' },
        { key: 'stock_quantity', label: 'Qty', class: 'text-right whitespace-nowrap' },
        { key: 'stock_status', label: 'Status', class: 'whitespace-nowrap' },
    ];

    function stockLabel(status: string) {
        return status
            .split('_')
            .map((w) => w[0].toUpperCase() + w.slice(1))
            .join(' ');
    }

    function timeAgo(iso: string) {
        const diff = Date.now() - new Date(iso).getTime();
        const mins = Math.round(diff / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return `${mins}m ago`;
        const hours = Math.round(mins / 60);
        if (hours < 24) return `${hours}h ago`;
        return `${Math.round(hours / 24)}d ago`;
    }
</script>

<AppHead title="Dashboard" />

<div class="flex h-full flex-1 flex-col gap-4 p-4">
    <div>
        <h1 class="text-xl font-semibold tracking-tight">
            Welcome back, {user?.name?.split(' ')[0]}
        </h1>
        <p class="text-sm text-muted-foreground">
            Here's what's happening in your catalog today.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {#if pageViews}
            <StatCard
                label="Website views today"
                value={pageViews.today}
                hint="{pageViews.lastSevenDays} in the last 7 days"
                icon={Eye}
            />
        {/if}
        {#if stats?.products}
            <StatCard
                label="Products"
                value={stats.products.total}
                hint="{stats.products.published} published · {stats.products.draft} draft"
                icon={Package}
            />
        {/if}
        {#if stats?.categories}
            <StatCard
                label="Categories"
                value={stats.categories.total}
                hint={stats?.subcategories ? `${stats.subcategories.total} subcategories` : undefined}
                icon={LayoutGrid}
            />
        {/if}
        {#if stats?.brands}
            <StatCard label="Brands" value={stats.brands.total} icon={Building2} />
        {/if}
        {#if stats?.customers}
            <StatCard
                label="Customers"
                value={stats.customers.total}
                hint={stats?.admins ? `${stats.admins.total} admin accounts` : undefined}
                icon={Users}
            />
        {/if}
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
        <div class="flex min-w-0 flex-col gap-4">
            {#if pageViews}
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Website views, last 14 days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TrendChart data={pageViews.trend} />
                    </CardContent>
                </Card>
            {/if}

            {#if categoryBreakdown}
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Products by category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BarChart data={categoryBreakdown} />
                    </CardContent>
                </Card>
            {/if}

            {#if monthlyTrend}
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">New products, last 6 months</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TrendChart data={monthlyTrend} />
                    </CardContent>
                </Card>
            {/if}

            {#if lowStock}
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <AlertTriangle class="size-4 text-destructive" />
                            Needs attention
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveTable items={lowStock} columns={lowStockColumns} key={(p) => p.id} empty="Everything is well stocked.">
                            {#snippet cell(item: LowStockProduct, column: Column)}
                                {#if column.key === 'stock_status'}
                                    <span
                                        class={[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            item.stock_status === 'out_of_stock'
                                                ? 'bg-destructive/10 text-destructive'
                                                : 'bg-muted text-muted-foreground',
                                        ].join(' ')}
                                    >
                                        {stockLabel(item.stock_status)}
                                    </span>
                                {:else}
                                    {item[column.key as keyof LowStockProduct]}
                                {/if}
                            {/snippet}
                            {#snippet actions(item: LowStockProduct)}
                                <Link
                                    href={adminProducts().url}
                                    class={buttonVariants({ variant: 'ghost', size: 'sm' })}
                                >
                                    View
                                </Link>
                            {/snippet}
                        </ResponsiveTable>
                    </CardContent>
                </Card>
            {/if}
        </div>

        <div class="flex min-w-0 flex-col gap-4">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Quick links</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-1.5">
                    {#if permissions?.products}
                        <Link href={adminProducts().url} class={buttonVariants({ variant: 'outline', class: 'justify-start' })}>
                            <PackageSearch class="size-4" /> Manage products
                        </Link>
                    {/if}
                    {#if permissions?.categories}
                        <Link href={adminCategories().url} class={buttonVariants({ variant: 'ghost', class: 'justify-start' })}>
                            <LayoutGrid class="size-4" /> Manage categories
                        </Link>
                    {/if}
                    {#if permissions?.brands}
                        <Link href={adminBrands().url} class={buttonVariants({ variant: 'ghost', class: 'justify-start' })}>
                            <Building2 class="size-4" /> Manage brands
                        </Link>
                    {/if}
                    {#if permissions?.tags}
                        <Link href={adminTags().url} class={buttonVariants({ variant: 'ghost', class: 'justify-start' })}>
                            <Tags class="size-4" /> Manage tags
                        </Link>
                    {/if}
                    {#if permissions?.enquiries}
                        <Link href={adminEnquiries().url} class={buttonVariants({ variant: 'ghost', class: 'justify-start' })}>
                            <Mail class="size-4" /> Contact enquiries
                        </Link>
                    {/if}
                    {#if permissions?.admins || permissions?.customers}
                        <Link href={adminUsers().url} class={buttonVariants({ variant: 'ghost', class: 'justify-start' })}>
                            <UserCog class="size-4" /> Users & roles
                        </Link>
                    {/if}
                    {#if permissions?.settings}
                        <Link href={adminSettings().url} class={buttonVariants({ variant: 'ghost', class: 'justify-start' })}>
                            <Settings class="size-4" /> Settings
                        </Link>
                    {/if}
                </CardContent>
            </Card>

            {#if recentActivity}
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ClipboardList class="size-4" /> Recent activity
                        </CardTitle>
                        <CardAction>
                            <Link href={activityLogIndex()} class="text-sm text-primary hover:underline">View all</Link>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        {#if recentActivity.length === 0}
                            <p class="text-sm text-muted-foreground">No activity yet.</p>
                        {:else}
                            <ul class="flex flex-col gap-3">
                                {#each recentActivity as entry (entry.id)}
                                    <li class="text-sm">
                                        <p class="break-words">{entry.description ?? entry.action}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {entry.user?.name ?? 'System'} · {timeAgo(entry.created_at)}
                                        </p>
                                    </li>
                                {/each}
                            </ul>
                        {/if}
                    </CardContent>
                </Card>
            {/if}
        </div>
    </div>
</div>
