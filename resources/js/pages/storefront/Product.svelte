<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Check from '@lucide/svelte/icons/check';
    import Info from '@lucide/svelte/icons/info';
    import Maximize2 from '@lucide/svelte/icons/maximize-2';
    import Phone from '@lucide/svelte/icons/phone';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import { buttonClass } from '@/components/storefront/button';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import MissingPage from '@/components/storefront/MissingPage.svelte';
    import Modal from '@/components/storefront/Modal.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import ProductGrid from '@/components/storefront/ProductGrid.svelte';
    import QuoteDialog from '@/components/storefront/QuoteDialog.svelte';
    import SectionHeading from '@/components/storefront/SectionHeading.svelte';
    import {
        brands,
        business,
        categories,
        pathSegment,
        products,
    } from '@/data/catalogue';
    import { catalogue } from '@/routes';
    import { show as categoryShow } from '@/routes/categories';

    const product = $derived(
        products.find((p) => p.slug === pathSegment(page.url, 1)),
    );

    let zoom = $state(false);
</script>

{#if product}
    {@const p = product}
    {@const category = categories.find((c) => c.id === p.category)}
    <PageMeta title={p.name} description={p.shortDescription} />

    <section class="container-wide pt-9 pb-16">
        <Breadcrumb
            items={[
                { label: 'Equipment', href: catalogue.url() },
                { label: category?.name ?? 'Equipment' },
                { label: p.name },
            ]}
        />
        <div class="grid gap-9 lg:grid-cols-2 lg:gap-16">
            <div>
                <div
                    class="relative aspect-square overflow-hidden rounded-md bg-photo"
                >
                    <img
                        src={p.image}
                        alt={p.name}
                        width={600}
                        height={600}
                        class="h-full w-full object-contain mix-blend-multiply"
                    />
                    <Button
                        variant="outline"
                        size="icon"
                        class="absolute right-4 bottom-4"
                        aria-label="Enlarge product image"
                        title="Enlarge product image"
                        onclick={() => (zoom = true)}
                    >
                        <Maximize2 />
                    </Button>
                    <span
                        class="absolute top-5 left-5 bg-background/90 px-3 py-1.5 text-[10px] text-primary"
                    >
                        Illustrative product photography
                    </span>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <span
                        class="h-16 w-16 overflow-hidden rounded border border-primary"
                    >
                        <img
                            src={p.image}
                            alt="{p.name} main view"
                            class="h-full w-full object-contain"
                        />
                    </span>
                    <span class="text-[10px] text-muted-foreground"
                        >Studio product view</span
                    >
                </div>
            </div>
            <div class="lg:pt-4">
                <div class="eyebrow mb-4">
                    {brands.find((b) => b.id === p.brand)?.name}
                </div>
                <h1 class="text-[31px] leading-tight font-semibold lg:text-[38px]">
                    {p.name}
                </h1>
                <Link
                    href={categoryShow.url(p.category)}
                    class="mt-4 inline-flex items-center gap-2 text-xs text-primary"
                >
                    {category?.name}
                    <ArrowUpRight class="size-3" />
                </Link>
                <div class="mt-5 flex gap-4 text-[10px] text-muted-foreground">
                    <span>Model: {p.sku}</span>
                    <span>•</span>
                    <span>{p.availability}</span>
                </div>
                <p
                    class="mt-3 border-y py-6 text-sm leading-7 text-muted-foreground"
                >
                    {p.shortDescription}. A considered addition to your clinical
                    workflow.
                </p>
                <div class="mt-6">
                    <div class="text-[27px] font-semibold">
                        {p.price ? p.priceLabel : 'Contact for Price'}
                    </div>
                    <span class="text-[10px] text-muted-foreground">
                        {p.price
                            ? 'Illustrative price in GBP. Not an offer for sale.'
                            : 'Let’s discuss the configuration that suits your practice.'}
                    </span>
                </div>
                <ul class="my-7 space-y-3">
                    {#each p.features as f (f)}
                        <li class="flex items-center gap-3 text-xs">
                            <Check class="size-4 text-primary" />
                            {f}
                        </li>
                    {/each}
                </ul>
                <div class="flex flex-wrap gap-3">
                    <a
                        href="tel:{business.telephone}"
                        class={buttonClass({ class: 'h-12 px-7 shadow-none' })}
                    >
                        <Phone />
                        Call for Price
                    </a>
                    {#key p.slug}
                        <QuoteDialog product={p.name} />
                    {/key}
                </div>
                <div
                    class="mt-7 flex gap-3 rounded bg-accent p-4 text-[10px] leading-6 text-muted-foreground"
                >
                    <Info class="mt-1 size-4 shrink-0 text-primary" />
                    <p>
                        Demo product. Images and specifications are illustrative,
                        not manufacturer-verified. Contact details are
                        placeholders. No online ordering.
                    </p>
                </div>
            </div>
        </div>
        <div
            class="mt-14 grid gap-10 border-t pt-12 lg:grid-cols-[1fr_1fr] lg:gap-20"
        >
            <div>
                <p class="eyebrow mb-3">Considered in every detail</p>
                <h2 class="text-2xl">Product overview</h2>
                <p class="section-intro text-sm leading-8">{p.description}</p>
                <h3 class="mt-8 mb-5 text-lg">Key features</h3>
                {#each p.features as f (f)}
                    <div class="flex gap-3 border-b py-3 text-sm">
                        <Check class="size-4 text-primary" />
                        {f}
                    </div>
                {/each}
            </div>
            <div>
                <h2 class="mb-5 text-2xl">Technical specifications</h2>
                <p class="mb-5 text-[10px] text-muted-foreground">
                    Illustrative specifications only. Confirm exact requirements
                    with the supplier.
                </p>
                <dl>
                    {#each Object.entries(p.specifications) as [key, value] (key)}
                        <div class="spec-row">
                            <dt class="text-muted-foreground">{key}</dt>
                            <dd>{value}</dd>
                        </div>
                    {/each}
                </dl>
            </div>
        </div>
    </section>

    <section class="border-t bg-secondary">
        <div class="container-wide section-space">
            <SectionHeading
                eyebrow="Complete your workflow"
                title="Explore Related Equipment"
                href={catalogue.url()}
            />
            <ProductGrid
                items={products.filter((x) => p.relatedProducts.includes(x.slug))}
            />
        </div>
    </section>

    <ContactBanner />

    <Modal
        bind:open={zoom}
        title={p.name}
        description="Illustrative studio photograph"
        class="max-h-[95dvh] max-w-3xl overflow-y-auto"
    >
        <img
            src={p.image}
            alt={p.name}
            class="h-[65dvh] w-full bg-photo object-contain"
        />
    </Modal>
{:else}
    <MissingPage />
{/if}
