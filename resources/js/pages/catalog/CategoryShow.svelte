<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { show as subcategoryShow } from '@/routes/catalog/subcategories';

    type Subcategory = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
    };

    let {
        category,
        subcategories,
    }: {
        category: { name: string; description: string | null };
        subcategories: Subcategory[];
    } = $props();

</script>

<AppHead title={category.name} />

<div class="mx-auto max-w-6xl px-4 py-8">

    <h1 class="mb-2 text-2xl font-semibold">{category.name}</h1>
    {#if category.description}
        <p class="mb-6 text-muted-foreground">{category.description}</p>
    {/if}

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {#each subcategories as subcategory (subcategory.id)}
            <Link
                href={subcategoryShow.url(subcategory.slug)}
                class="rounded-lg border p-4 hover:shadow-md"
            >
                <h2 class="font-medium">{subcategory.name}</h2>
                {#if subcategory.description}
                    <p class="mt-1 text-xs text-muted-foreground">{subcategory.description}</p>
                {/if}
            </Link>
        {:else}
            <p class="col-span-full text-muted-foreground">
                No subcategories yet.
            </p>
        {/each}
    </div>
</div>
