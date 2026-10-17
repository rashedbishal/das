<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { show as categoryShow } from '@/routes/catalog/categories';

    type Category = {
        id: number;
        name: string;
        slug: string;
        image: string | null;
        subcategories: { id: number }[];
    };

    let { categories }: { categories: Category[] } = $props();
</script>

<AppHead title="Categories" />

<div class="mx-auto max-w-6xl px-4 py-8">

    <h1 class="mb-6 text-2xl font-semibold">Categories</h1>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {#each categories as category (category.id)}
            <Link
                href={categoryShow.url(category.slug)}
                class="rounded-lg border p-4 hover:shadow-md"
            >
                <h2 class="font-medium">{category.name}</h2>
                {#if category.subcategories.length}
                    <p class="mt-1 text-xs text-muted-foreground">
                        {category.subcategories.length} subcategories
                    </p>
                {/if}
            </Link>
        {:else}
            <p class="col-span-full text-muted-foreground">
                No categories yet.
            </p>
        {/each}
    </div>
</div>
