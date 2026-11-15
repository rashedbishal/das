<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { categories, loadCategories } from '@/data/catalogue';
    import { show } from '@/routes/categories';

    // Product counts come with the category list (one request, not one per category).
    loadCategories().catch(() => {});

    const counts = $derived(
        Object.fromEntries($categories.map((c) => [c.slug, c.productsCount])),
    );
</script>

<PageMeta
    title="Equipment Categories"
    description="Browse DAS dental equipment by category, from instruments to laboratory materials."
/>

<section class="container-wide pt-10 pb-16">
    <Breadcrumb items={[{ label: 'Categories' }]} />
    <p class="eyebrow mb-3">Built around your practice</p>
    <h1 class="page-heading">Everything Your Practice Needs</h1>
    <p class="section-intro mb-10 max-w-xl">
        Explore the tools, technology and clinical essentials that bring your
        practice together.
    </p>
    <div class="grid gap-6 md:grid-cols-2">
        {#each $categories as c, i (c.id)}
            <Link
                href={show.url(c.slug)}
                class="group grid min-h-60 grid-cols-[1.1fr_1fr] overflow-hidden rounded-md border"
            >
                <div class="flex flex-col items-start p-6 lg:p-8">
                    <span class="mb-5 text-[10px] text-primary"
                        >0{i + 1} / EQUIPMENT</span
                    >
                    <h2 class="text-xl leading-snug">{c.name}</h2>
                    <p class="mt-3 text-xs leading-6 text-muted-foreground">
                        {c.description}
                    </p>
                    <span
                        class="mt-auto flex items-center gap-2 pt-5 text-[11px] text-primary"
                    >
                        {counts[c.slug] ?? 0} products
                        <ArrowUpRight class="size-4" />
                    </span>
                </div>
                <div class="bg-secondary">
                    <img
                        class="h-full w-full object-contain mix-blend-multiply transition-transform group-hover:scale-[1.03]"
                        src={c.image}
                        alt={c.name}
                        loading="lazy"
                        width={384}
                        height={512}
                    />
                </div>
            </Link>
        {/each}
    </div>
</section>

<ContactBanner />
