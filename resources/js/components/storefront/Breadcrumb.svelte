<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { home } from '@/routes';

    let { items }: { items: { label: string; href?: string }[] } = $props();
</script>

<nav aria-label="Breadcrumb" class="breadcrumb">
    <Link href={home.url()}>Home</Link>
    {#each items as item, n (n)}
        <span class="contents">
            <span class="opacity-50">/</span>
            {#if item.href}
                <Link href={item.href} class="hover:text-primary">
                    {item.label}
                </Link>
            {:else}
                <span class="text-foreground">{item.label}</span>
            {/if}
        </span>
    {/each}
</nav>
