<script module lang="ts">
    import { index } from '@/routes/admin/seo';

    export const layout = {
        breadcrumbs: [{ title: 'SEO Health', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import Pencil from '@lucide/svelte/icons/pencil';
    import AppHead from '@/components/AppHead.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import { Badge } from '@/components/ui/badge';
    import { buttonVariants } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { index as brandsIndex } from '@/routes/admin/brands';
    import { index as categoriesIndex } from '@/routes/admin/categories';
    import { index as productsIndex } from '@/routes/admin/products';
    import { index as subcategoriesIndex } from '@/routes/admin/subcategories';

    type Item = {
        type: 'product' | 'category' | 'subcategory' | 'brand';
        id: number;
        name: string;
        url: string;
        issues: string[];
    };
    type SiteCheck = { label: string; ok: boolean; hint: string };

    let {
        report,
        sitemapUrl,
    }: {
        report: { score: number; pages: number; pagesWithIssues: number; items: Item[]; site: SiteCheck[] };
        sitemapUrl: string;
    } = $props();

    const typeOptions = [
        { value: 'all', label: 'All page types' },
        { value: 'product', label: 'Products' },
        { value: 'category', label: 'Categories' },
        { value: 'subcategory', label: 'Subcategories' },
        { value: 'brand', label: 'Brands' },
    ];

    let type = $state('all');
    let search = $state('');

    const filtered = $derived(
        report.items.filter(
            (item) =>
                (type === 'all' || item.type === type) &&
                item.name.toLowerCase().includes(search.trim().toLowerCase()),
        ),
    );
    const siteDone = $derived(report.site.filter((check) => check.ok).length);

    function editUrl(item: Item): string {
        switch (item.type) {
            case 'product':
                return productsIndex.url({ query: { search: item.name } });
            case 'category':
                return categoriesIndex.url();
            case 'subcategory':
                return subcategoriesIndex.url();
            default:
                return brandsIndex.url();
        }
    }
</script>

<AppHead title="SEO Health" />

<div class="w-full space-y-6 p-4">
    <div>
        <h1 class="text-xl font-semibold">SEO Health</h1>
        <p class="text-sm text-muted-foreground">
            What still holds pages back in Google. Fix the items below one by one; the list updates as you go.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader><CardTitle class="text-base">Page score</CardTitle></CardHeader>
            <CardContent>
                <p class="text-3xl font-semibold tabular-nums">{report.score}%</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {report.pages - report.pagesWithIssues} of {report.pages} storefront pages have no issues.
                </p>
            </CardContent>
        </Card>
        <Card class="md:col-span-2">
            <CardHeader>
                <CardTitle class="text-base">Site setup ({siteDone} of {report.site.length} done)</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-2 text-sm">
                    {#each report.site as check (check.label)}
                        <li class="flex items-start gap-2">
                            {#if check.ok}
                                <CircleCheck class="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                <span>{check.label}</span>
                            {:else}
                                <CircleAlert class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                <span>
                                    {check.label}
                                    <span class="block text-xs text-muted-foreground">{check.hint}</span>
                                </span>
                            {/if}
                        </li>
                    {/each}
                </ul>
                <p class="mt-4 text-xs text-muted-foreground">
                    Sitemap for Search Console: <a href={sitemapUrl} target="_blank" rel="noopener" class="font-mono text-primary">{sitemapUrl}</a>
                </p>
            </CardContent>
        </Card>
    </div>

    <div>
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <h2 class="mr-auto text-lg font-semibold">Pages to improve ({report.pagesWithIssues})</h2>
            <Input bind:value={search} type="search" placeholder="Search pages..." aria-label="Search pages" class="sm:max-w-xs" />
            <FormSelect bind:value={type} options={typeOptions} class="w-44" />
        </div>

        <ResponsiveTable
            items={filtered}
            key={(item) => `${item.type}-${item.id}`}
            columns={[
                { key: 'name', label: 'Page' },
                { key: 'issues', label: 'What to fix' },
            ]}
            empty={report.items.length === 0 ? 'Every page looks good. Nice work!' : 'No pages match these filters.'}
        >
            {#snippet cell(item, column)}
                {#if column.key === 'name'}
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="break-words">{item.name}</span>
                        <Badge variant="outline" class="capitalize">{item.type}</Badge>
                    </div>
                {:else}
                    <ul class="list-disc space-y-1 pl-4 text-sm">
                        {#each item.issues as issue (issue)}
                            <li>{issue}</li>
                        {/each}
                    </ul>
                {/if}
            {/snippet}
            {#snippet actions(item)}
                <Link href={editUrl(item)} aria-label="Edit {item.name}" class={buttonVariants({ variant: 'ghost', size: 'sm' })}>
                    <Pencil class="size-4" />
                </Link>
                <a
                    href={item.url}
                    target="_blank"
                    rel="noopener"
                    aria-label="View {item.name} on the site"
                    class={buttonVariants({ variant: 'ghost', size: 'sm' })}
                >
                    <ArrowUpRight class="size-4" />
                </a>
            {/snippet}
        </ResponsiveTable>
    </div>
</div>
