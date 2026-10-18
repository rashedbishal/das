<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Wordmark from '@/components/storefront/Wordmark.svelte';
    import { home } from '@/routes';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();
</script>

<svelte:head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin="anonymous"
    />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
    />
</svelte:head>

<div class="storefront">
    <main
        class="flex min-h-svh flex-col items-center justify-center bg-secondary px-4 py-10 sm:px-6"
    >
        <div class="flex w-full max-w-[400px] flex-col items-center gap-8">
            <Link href={home()} aria-label="Home">
                <Wordmark />
            </Link>

            <div
                class="w-full rounded-xl border bg-background p-7 shadow-sm sm:p-8"
            >
                {#if title || description}
                    <div class="mb-7 text-center">
                        {#if title}
                            <h1 class="text-xl leading-tight font-semibold">
                                {title}
                            </h1>
                        {/if}
                        {#if description}
                            <p
                                class="mt-1.5 text-sm leading-relaxed text-muted-foreground"
                            >
                                {description}
                            </p>
                        {/if}
                    </div>
                {/if}
                {@render children?.()}
            </div>
        </div>
    </main>
</div>
