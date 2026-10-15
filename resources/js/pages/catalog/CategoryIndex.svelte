<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import { login } from '@/routes';

    type Category = {
        id: number;
        name: string;
        slug: string;
        image: string | null;
        children: Category[];
    };

    let { categories }: { categories: Category[] } = $props();
    const auth = $derived(page.props.auth);
</script>

<AppHead title="Categories" />

<div class="mx-auto max-w-6xl px-4 py-8">
    <header class="mb-8 flex items-center justify-between">
        <Link href="/" class="text-lg font-semibold">DAS Dental</Link>
        <nav class="flex items-center gap-4 text-sm">
            <Link href="/products">Products</Link>
            {#if auth.user}
                <Link href="/dashboard">Dashboard</Link>
            {:else}
                <Link href={login()}>Log in</Link>
            {/if}
        </nav>
    </header>

    <h1 class="mb-6 text-2xl font-semibold">Categories</h1>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {#each categories as category (category.id)}
            <Link
                href={`/categories/${category.slug}`}
                class="rounded-lg border p-4 hover:shadow-md"
            >
                <h2 class="font-medium">{category.name}</h2>
                {#if category.children.length}
                    <p class="mt-1 text-xs text-muted-foreground">
                        {category.children.length} subcategories
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

<ContactWidgets />
