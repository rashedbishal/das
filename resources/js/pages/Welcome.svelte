<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';
    import { index as productsIndex, show as productShow } from '@/routes/products';
    import { index as categoriesIndex } from '@/routes/categories';

    type ProductCard = {
        id: number;
        name: string;
        slug: string;
        main_image: string | null;
        regular_price: string;
        sale_price: string | null;
        currency: string;
    };

    type HomepageSection = {
        id: number;
        type: 'banner' | 'section';
        title: string;
        subtitle: string | null;
        image: string | null;
        link_url: string | null;
    };

    let {
        sections = [],
        featuredProducts,
    }: {
        sections?: HomepageSection[];
        featuredProducts: { data: ProductCard[] };
    } = $props();

    const auth = $derived(page.props.auth);
    const siteSettings = $derived(page.props.siteSettings);
</script>

<AppHead title="Home" />

<div class="min-h-screen bg-background">
    <header class="border-b">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <Link href="/" class="text-lg font-semibold">{siteSettings.name}</Link>
            <nav class="flex items-center gap-4 text-sm">
                <Link href={toUrl(productsIndex())}>Products</Link>
                <Link href={toUrl(categoriesIndex())}>Categories</Link>
                {#if auth.user}
                    <Link href={toUrl(dashboard())}>Dashboard</Link>
                {:else}
                    <Link href={toUrl(login())}>Log in</Link>
                    <Link
                        href={toUrl(register())}
                        class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground"
                    >
                        Register
                    </Link>
                {/if}
            </nav>
        </div>
    </header>

    {#each sections as section (section.id)}
        <section class="border-b bg-muted/30 px-4 py-10">
            <div class="mx-auto max-w-6xl">
                <h2 class="text-xl font-semibold">{section.title}</h2>
                {#if section.subtitle}
                    <p class="mt-1 text-muted-foreground">{section.subtitle}</p>
                {/if}
                {#if section.link_url}
                    <Link href={section.link_url} class="mt-3 inline-block text-sm text-primary underline">
                        Learn more
                    </Link>
                {/if}
            </div>
        </section>
    {/each}

    <main class="mx-auto max-w-6xl px-4 py-10">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-xl font-semibold">Featured products</h2>
            <Link href={toUrl(productsIndex())} class="text-sm text-primary underline">
                View all products
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {#each featuredProducts.data as product (product.id)}
                <Link
                    href={productShow.url(product.slug)}
                    class="group rounded-lg border p-3 transition-shadow hover:shadow-md"
                >
                    <div class="mb-3 aspect-square overflow-hidden rounded bg-muted">
                        {#if product.main_image}
                            <img
                                src={product.main_image}
                                alt={product.name}
                                class="h-full w-full object-cover"
                            />
                        {/if}
                    </div>
                    <h3 class="font-medium group-hover:underline">{product.name}</h3>
                    <p class="mt-1 text-sm font-semibold">
                        {product.currency} {product.sale_price ?? product.regular_price}
                    </p>
                </Link>
            {:else}
                <p class="col-span-full text-muted-foreground">
                    No featured products yet.
                </p>
            {/each}
        </div>
    </main>
</div>

<ContactWidgets />
