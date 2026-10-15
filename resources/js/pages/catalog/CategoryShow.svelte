<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import { show as subcategoryShow } from '@/routes/subcategories';

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

    const auth = $derived(page.props.auth);
</script>

<AppHead title={category.name} />

<div class="mx-auto max-w-6xl px-4 py-8">
    <header class="mb-8 flex items-center justify-between">
        <Link href="/" class="text-lg font-semibold">DAS Dental</Link>
        <nav class="flex items-center gap-4 text-sm">
            <Link href="/categories">Categories</Link>
            {#if auth.user}
                <Link href="/dashboard">Dashboard</Link>
            {/if}
        </nav>
    </header>

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

<ContactWidgets />
