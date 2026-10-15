<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import Menu from '@lucide/svelte/icons/menu';
    import Phone from '@lucide/svelte/icons/phone';
    import Search from '@lucide/svelte/icons/search';
    import {
        categories,
        fetchProducts,
        filterQuery,
        loadCategories,
        type ProductCard,
    } from '@/data/catalogue';
    import { FALLBACK_CONTACT, resolvePhone } from '@/lib/contact';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { about, brands, catalogue, contact, home } from '@/routes';
    import { index as categoriesIndex } from '@/routes/categories';
    import { show as productShow } from '@/routes/products';
    import Button from './Button.svelte';
    import { buttonClass } from './button';
    import Drawer from './Drawer.svelte';
    import Modal from './Modal.svelte';
    import Wordmark from './Wordmark.svelte';

    loadCategories();

    const phone = $derived(resolvePhone(page.props.contactWidgets.whatsapp));

    const links = [
        ['Home', home.url()],
        ['Shop Equipment', catalogue.url()],
        ['Categories', categoriesIndex.url()],
        ['Brands', brands.url()],
        ['About Us', about.url()],
        ['Contact', contact.url()],
    ] as const;

    const current = currentUrlState();

    let mobile = $state(false);
    let searchOpen = $state(false);
    let q = $state('');
    let mega = $state(false);
    let matches = $state<ProductCard[]>([]);

    $effect(() => {
        const term = q.trim();

        if (!term) {
            matches = [];

            return;
        }

        fetchProducts({ q: term }, 1, 4).then((result) => {
            matches = result.items;
        });
    });

    function goToCatalogue(term: string) {
        searchOpen = false;
        mobile = false;
        router.visit(catalogue.url({ query: filterQuery({ q: term }) }));
    }
</script>

<div class="bg-deep text-on-deep">
    <div
        class="container-wide flex min-h-8 items-center justify-between gap-4 text-[9px] sm:text-[10px]"
    >
        <span>
            Professional Dental Equipment <span class="mx-2 opacity-40">|</span>
            Expert Product Assistance
        </span>
        <a href="mailto:{FALLBACK_CONTACT.email}" class="hidden opacity-80 sm:block">
            Let’s find the right equipment <ArrowUpRight
                class="ml-1 inline size-3"
            />
        </a>
    </div>
</div>
<header class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur-md">
    <div
        class="container-wide flex h-[86px] items-center justify-between gap-6"
    >
        <Link href={home.url()} aria-label="DENTORA home">
            <Wordmark />
        </Link>
        <nav
            aria-label="Main navigation"
            class="hidden items-center gap-[25px] text-[11px] font-medium xl:flex"
        >
            {#each links as [name, path] (path)}
                <div class="relative">
                    <Link
                        href={path}
                        class="py-8 transition-colors hover:text-primary {current.currentUrl ===
                        path
                            ? 'text-primary'
                            : ''}"
                    >
                        {name}
                    </Link>
                    {#if name === 'Categories'}
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Browse category menu"
                            aria-expanded={mega}
                            onclick={() => (mega = !mega)}
                            class="ml-0.5 size-5"
                        >
                            <ChevronDown class="size-3" />
                        </Button>
                    {/if}
                </div>
            {/each}
        </nav>
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                aria-label="Search equipment"
                title="Search equipment"
                onclick={() => (searchOpen = true)}
            >
                <Search class="size-[19px]" />
            </Button>
            <div class="hidden h-6 border-l md:block"></div>
            <a
                href="tel:{phone.tel}"
                class={buttonClass({
                    class: 'hidden h-10 px-5 text-[11px] shadow-none md:inline-flex',
                })}
            >
                <Phone class="size-3.5" />
                Call Sales
            </a>
            <Button
                variant="ghost"
                size="icon"
                class="xl:hidden"
                aria-label="Open navigation"
                aria-expanded={mobile}
                onclick={() => (mobile = !mobile)}
            >
                <Menu />
            </Button>
        </div>
    </div>
    {#if mega}
        <div class="absolute inset-x-0 border-b bg-background shadow-lg">
            <div class="container-wide grid grid-cols-4 gap-5 py-7">
                {#each $categories as c (c.id)}
                    <Link
                        href={catalogue.url({ query: { category: c.slug } })}
                        onclick={() => (mega = false)}
                        class="flex items-center gap-3 hover:text-primary"
                    >
                        <img
                            src={c.image}
                            alt=""
                            class="h-14 w-14 rounded bg-secondary object-contain"
                        />
                        <span class="text-xs">{c.name}</span>
                    </Link>
                {/each}
            </div>
        </div>
    {/if}
</header>

<Drawer bind:open={mobile} label="Navigation" class="overflow-y-auto">
    <h2 class="mt-6 text-lg font-semibold text-foreground">
        <Wordmark />
    </h2>
    <p class="mt-3 text-sm text-muted-foreground">
        Professional dental equipment
    </p>
    <nav class="mt-8 flex flex-col" aria-label="Mobile navigation">
        {#each links as [name, path] (path)}
            <Link
                href={path}
                onclick={() => (mobile = false)}
                class="flex justify-between border-b py-4"
            >
                {name}
                <ArrowUpRight class="size-4 text-primary" />
            </Link>
        {/each}
    </nav>
    <a
        href="tel:{phone.tel}"
        class={buttonClass({ class: 'mt-8 w-full' })}
    >
        <Phone />
        Call Sales
    </a>
</Drawer>

<Modal
    bind:open={searchOpen}
    title="Find your next piece of equipment."
    description="Search products, brands or specifications."
    class="max-w-2xl"
>
    <form
        onsubmit={(event) => {
            event.preventDefault();
            goToCatalogue(q);
        }}
        class="mt-3 flex gap-2"
    >
        <input
            class="field min-w-0 flex-1"
            data-autofocus
            bind:value={q}
            placeholder="Search dental equipment…"
            aria-label="Search products"
        />
        <Button type="submit" class="h-11" aria-label="Run search">
            <Search />
        </Button>
    </form>
    <div class="mt-1 flex flex-wrap gap-2">
        {#each ['Dental chair', 'Autoclave', 'Handpiece'] as term (term)}
            <Button
                variant="outline"
                size="sm"
                onclick={() => {
                    q = term;
                    goToCatalogue(term);
                }}
            >
                {term}
                <ArrowUpRight class="size-3" />
            </Button>
        {/each}
    </div>
    {#if matches.length}
        <div class="mt-3">
            {#each matches as p (p.id)}
                <Link
                    href={productShow.url(p.slug)}
                    onclick={() => (searchOpen = false)}
                    class="flex items-center gap-4 border-t py-3 text-xs"
                >
                    <img
                        src={p.image}
                        class="size-12 bg-secondary object-contain"
                        alt=""
                    />
                    {p.name}
                    <ArrowRight class="ml-auto size-4" />
                </Link>
            {/each}
        </div>
    {/if}
</Modal>
