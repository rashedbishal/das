<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Check from '@lucide/svelte/icons/check';
    import Headphones from '@lucide/svelte/icons/headphones';
    import Layers3 from '@lucide/svelte/icons/layers';
    import MessagesSquare from '@lucide/svelte/icons/messages-square';
    import Phone from '@lucide/svelte/icons/phone';
    import Plus from '@lucide/svelte/icons/plus';
    import ScanLine from '@lucide/svelte/icons/scan-line';
    import hero from '@/assets/storefront/dental-suite.jpg';
    import { buttonClass } from '@/components/storefront/button';
    import CategoryTiles from '@/components/storefront/CategoryTiles.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import HeroBanners, {
        type Banner,
    } from '@/components/storefront/HeroBanners.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import ProductGrid from '@/components/storefront/ProductGrid.svelte';
    import SectionHeading from '@/components/storefront/SectionHeading.svelte';
    import TrustStrip from '@/components/storefront/TrustStrip.svelte';
    import { revealOnView } from '@/lib/motion.svelte';
    import { brands, loadBrands, mapProductCard, type RawProductCard } from '@/data/catalogue';
    import { brands as brandsRoute, catalogue, contact, home } from '@/routes';
    import { index as categoriesIndex } from '@/routes/categories';

    let {
        banners = [],
        featuredProducts: rawFeatured = [],
    }: { banners?: Banner[]; featuredProducts?: RawProductCard[] } = $props();

    // Decorative sections: if these fail, they simply stay empty.
    loadBrands().catch(() => {});

    // Rendered server-side so the product links are in the HTML.
    const featuredProducts = $derived(rawFeatured.map(mapProductCard));

    const reasons = [
        {
            icon: Layers3,
            title: 'A Curated Product Range',
            text: 'Explore a focused range of clinical equipment, instruments and everyday essentials.',
        },
        {
            icon: ScanLine,
            title: 'Clear Product Information',
            text: 'Find the specifications and details you need to make a more informed decision.',
        },
        {
            icon: Headphones,
            title: 'Professional Buying Assistance',
            text: 'Talk through your requirements and explore options suited to your clinical workflow.',
        },
        {
            icon: MessagesSquare,
            title: 'Direct Access to Our Sales Team',
            text: 'Get a human perspective on your next equipment investment. Just call or get in touch.',
        },
    ];
</script>

<!-- Home uses the CMS defaults (Settings → Catalog & SEO) when they are set. -->
<PageMeta
    title={page.props.seo.defaultTitle?.trim() || `Professional Dental Equipment | ${page.props.siteSettings.name}`}
    exactTitle
    description={page.props.seo.defaultDescription?.trim() ||
        'Discover precision dental equipment, clinical essentials and personal product guidance for your practice.'}
    canonical={home.url()}
/>

{#if banners.length > 0}
    <HeroBanners {banners} />
{:else}
<section class="hero">
    <img
        class="hero-image"
        src={hero}
        alt="A mint dental treatment chair and precision equipment in a modern sunlit clinic"
        width={1920}
        height={1024}
        fetchpriority="high"
    />
    <div class="container-wide">
        <div class="hero-copy hero-entrance">
            <div class="eyebrow mb-5 flex items-center gap-2">
                <span class="w-6 border-t border-primary"></span>
                Equipped for better care
            </div>
            <h1>
                Precision Equipment.
                <br />
                <span>Better Dental Care.</span>
            </h1>
            <p class="section-intro">
                Discover professional dental equipment and clinical essentials
                for modern practices. Thoughtfully selected. Expertly supported.
            </p>
            <div class="hero-actions">
                <Link
                    href={catalogue.url()}
                    class={buttonClass({ class: 'h-12 px-6 text-xs shadow-none' })}
                >
                    Explore Equipment
                    <ArrowUpRight class="ml-3 size-4" />
                </Link>
                <Link
                    href={contact.url()}
                    class={buttonClass({
                        variant: 'outline',
                        class: 'h-12 border-foreground/15 bg-background/70 px-6 text-xs shadow-none',
                    })}
                >
                    <Phone class="size-3.5" />
                    Talk to Our Team
                </Link>
            </div>
            <div class="hero-note">
                <Check class="size-3.5 text-primary" />
                Professional equipment<span class="mx-1 opacity-40">/</span
                >Personal guidance
                <span class="mx-1 opacity-40">/</span>No guesswork
            </div>
        </div>
    </div>
    <div class="hero-corner">DESIGNED AROUND YOUR PRACTICE</div>
</section>
{/if}

<TrustStrip />

<section
    class="practice-needs sf-own-motion container-wide section-space"
    {@attach revealOnView}
>
    <SectionHeading
        eyebrow="Explore the collection"
        title="Everything Your Practice Needs"
        description="From everyday essentials to your next clinical investment."
        href={categoriesIndex.url()}
        label="Explore all categories"
    />
    <CategoryTiles />
    <Link
        href={categoriesIndex.url()}
        class="practice-needs-more mt-7 flex items-center justify-center gap-2 text-xs text-primary sm:hidden"
    >
        Explore all categories
        <ArrowUpRight class="size-4" />
    </Link>
</section>

<section class="border-y bg-secondary">
    <div class="container-wide section-space">
        <SectionHeading
            eyebrow="Selected for your practice"
            title="Featured Equipment"
            description="Explore carefully selected equipment for modern dental practices."
            href={catalogue.url()}
        />
        <ProductGrid items={featuredProducts.slice(0, 8)} />
        <div class="mt-9 text-center">
            <Link
                href={catalogue.url()}
                class={buttonClass({
                    variant: 'outline',
                    class: 'h-11 bg-transparent px-7 text-xs shadow-none',
                })}
            >
                Discover the Full Catalogue
                <ArrowRight class="ml-3" />
            </Link>
        </div>
    </div>
</section>

<section class="editorial-banner sf-own-motion" {@attach revealOnView}>
    <img
        src={hero}
        width={1920}
        height={1024}
        loading="lazy"
        alt="Modern dental equipment in a bright, carefully designed clinical space"
    />
    <div class="container-wide py-16 lg:py-[70px]">
        <div
            class="eb-eyebrow mb-5 flex items-center gap-2 text-[9px] tracking-[1.7px] uppercase opacity-70"
        >
            <Plus class="size-3" />A practice with possibility
        </div>
        <h2>
            <span class="eb-line"><span>Equipping the Future</span></span>
            <span class="eb-line"><span>of Dentistry.</span></span>
        </h2>
        <p class="eb-copy mt-5 text-xs">
            From essential instruments to advanced clinical technology, discover
            equipment suited to your practice.
        </p>
        <Link
            href={catalogue.url()}
            class={buttonClass({
                class: 'eb-cta mt-7 h-11 bg-background px-6 text-xs text-foreground shadow-none hover:bg-accent',
            })}
        >
            Explore Our Catalogue
            <ArrowUpRight class="ml-3" />
        </Link>
    </div>
</section>

<section class="container-wide border-b py-14 lg:py-16">
    <div
        class="sf-reveal mb-9 flex flex-col justify-between gap-3 md:flex-row md:items-center"
    >
        <div>
            <p class="eyebrow mb-2">Distinct expertise. Shared purpose.</p>
            <h2 class="text-xl">A considered selection of brands.</h2>
        </div>
        <Link
            href={brandsRoute.url()}
            class="flex items-center gap-2 text-[11px] text-muted-foreground"
        >
            Meet the brands
            <ArrowUpRight class="size-4" />
        </Link>
    </div>
    <div
        class="sf-reveal grid grid-cols-2 items-center gap-x-6 gap-y-8 sm:grid-cols-3 lg:grid-cols-6 lg:gap-x-10"
    >
        {#each $brands as b (b.id)}
            <!-- The logo alone when there is one (its alt text names the
                 brand); otherwise the name in small text. -->
            <Link
                href={catalogue.url({ query: { brand: b.slug } })}
                class="flex h-16 min-w-0 items-center justify-center text-center opacity-85 transition-opacity hover:opacity-100 lg:h-20"
            >
                {#if b.logo}
                    <img
                        src={b.logo}
                        alt={b.name}
                        loading="lazy"
                        decoding="async"
                        class="size-full object-contain"
                    />
                {:else}
                    <span
                        class="w-full truncate text-xs font-semibold text-muted-foreground"
                    >
                        {b.name}
                    </span>
                {/if}
            </Link>
        {/each}
    </div>
</section>

<section
    class="better-way sf-own-motion container-wide section-space"
    {@attach revealOnView}
>
    <SectionHeading
        eyebrow="More than equipment"
        title="A Better Way to Source Dental Equipment"
        description="Considered choices. Clear information. A conversation that puts your practice first."
    />
    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-9">
        {#each reasons as { icon: Icon, title, text }, i (title)}
            <div class="better-way-card relative border-t pt-6">
                <div class="mb-5 flex items-center justify-between">
                    <Icon class="size-7 text-primary" strokeWidth={1.3} />
                    <span class="better-way-num text-[10px] text-muted-foreground"
                        >0{i + 1}</span
                    >
                </div>
                <h3 class="text-sm leading-6">{title}</h3>
                <p class="mt-3 text-[11px] leading-7 text-muted-foreground">
                    {text}
                </p>
            </div>
        {/each}
    </div>
</section>

<ContactBanner />
