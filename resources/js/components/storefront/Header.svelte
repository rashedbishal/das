<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import LogOut from '@lucide/svelte/icons/log-out';
    import Menu from '@lucide/svelte/icons/menu';
    import Phone from '@lucide/svelte/icons/phone';
    import Search from '@lucide/svelte/icons/search';
    import UserRound from '@lucide/svelte/icons/user-round';
    import { cubicOut, quintOut } from 'svelte/easing';
    import { fade } from 'svelte/transition';
    import {
        categories,
        fetchProducts,
        filterQuery,
        loadCategories,
        type Category,
        type ProductCard,
    } from '@/data/catalogue';
    import { FALLBACK_CONTACT, resolvePhone } from '@/lib/contact';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import {
        about,
        brands,
        catalogue,
        contact,
        dashboard,
        home,
        login,
        logout,
    } from '@/routes';
    import { index as categoriesIndex } from '@/routes/categories';
    import { show as productShow } from '@/routes/products';
    import Button from './Button.svelte';
    import { buttonClass } from './button';
    import Drawer from './Drawer.svelte';
    import Modal from './Modal.svelte';
    import Wordmark from './Wordmark.svelte';

    loadCategories();

    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const email = $derived(
        page.props.siteSettings.contactEmail || FALLBACK_CONTACT.email,
    );
    const user = $derived(page.props.auth.user);
    const isAdmin = $derived(user?.user_type === 'admin');

    const links = [
        ['Home', home.url()],
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
    let expandedCategory = $state<Category | null>(null);
    let matches = $state<ProductCard[]>([]);
    let closeMegaTimeout: ReturnType<typeof setTimeout> | undefined;
    // Bumped every time the menu genuinely opens from closed, so the keyed
    // category grid remounts (and replays its slide-in) even if the panel
    // itself stayed mounted across a quick hover-out/hover-in.
    let megaOpenId = $state(0);

    function openMega() {
        clearTimeout(closeMegaTimeout);

        if (!mega) {
            megaOpenId += 1;
        }

        mega = true;
    }

    function scheduleCloseMega() {
        clearTimeout(closeMegaTimeout);
        closeMegaTimeout = setTimeout(() => {
            mega = false;
            expandedCategory = null;
        }, 150);
    }

    function toggleMega() {
        clearTimeout(closeMegaTimeout);

        if (mega) {
            mega = false;
            expandedCategory = null;
        } else {
            megaOpenId += 1;
            mega = true;
        }
    }

    function selectCategory(category: Category) {
        expandedCategory = expandedCategory?.id === category.id ? null : category;
    }

    // Drops and unfurls from the nav bar, rather than a mechanical height-slide.
    function panelReveal(_node: HTMLElement, { duration = 320, delay = 0 } = {}) {
        return {
            duration,
            delay,
            easing: quintOut,
            css: (t: number) => `
                opacity: ${t};
                transform: translateY(${(1 - t) * -10}px);
            `,
        };
    }


    // Horizontal cross-fade used when swapping between the category and subcategory views.
    function viewSwap(_node: HTMLElement, { duration = 320 } = {}) {
        return {
            duration,
            easing: quintOut,
            css: (t: number) => `
                opacity: ${t};
                transform: translateX(${(1 - t) * 18}px);
            `,
        };
    }

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
        <a href="mailto:{email}" class="hidden opacity-80 sm:block">
            Let’s find the right equipment <ArrowUpRight
                class="ml-1 inline size-3"
            />
        </a>
    </div>
</div>
<header class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur-md">
    <div
        class="container-wide flex h-[86px] items-center justify-between gap-3 sm:gap-6"
    >
        <Link href={home.url()} aria-label="DAS home">
            <Wordmark />
        </Link>
        <nav
            aria-label="Main navigation"
            class="hidden items-center gap-[25px] text-sm font-medium xl:flex"
        >
            {#each links as [name, path] (path)}
                {#if name === 'Categories'}
                    <div
                        role="presentation"
                        onmouseenter={openMega}
                        onmouseleave={scheduleCloseMega}
                    >
                        <button
                            type="button"
                            class="flex items-center gap-1 py-8 transition-colors hover:text-primary {mega
                                ? 'text-primary'
                                : ''}"
                            aria-label="Browse category menu"
                            aria-expanded={mega}
                            onclick={toggleMega}
                        >
                            {name}
                            <ChevronDown
                                class="size-3 transition-transform duration-300 ease-out {mega ? 'rotate-180' : ''}"
                            />
                        </button>
                    </div>
                {:else}
                    <Link
                        href={path}
                        class="py-8 transition-colors hover:text-primary {current.currentUrl ===
                        path
                            ? 'text-primary'
                            : ''}"
                    >
                        {name}
                    </Link>
                {/if}
            {/each}
        </nav>
        <div class="flex items-center gap-1 sm:gap-3">
            <Button
                variant="ghost"
                size="icon"
                aria-label="Search equipment"
                title="Search equipment"
                onclick={() => (searchOpen = true)}
            >
                <Search class="size-[19px]" />
            </Button>
            {#if !user}
                <Link
                    href={login()}
                    class={buttonClass({ variant: 'ghost', size: 'icon' })}
                    aria-label="Log in"
                    title="Log in"
                >
                    <UserRound class="size-[19px]" />
                </Link>
            {:else}
                {#if isAdmin}
                    <Link
                        href={dashboard()}
                        class={buttonClass({
                            variant: 'ghost',
                            size: 'icon',
                            class: 'hidden sm:inline-flex',
                        })}
                        aria-label="Dashboard"
                        title="Dashboard"
                    >
                        <LayoutDashboard class="size-[19px]" />
                    </Link>
                {/if}
                <Link
                    href={logout()}
                    as="button"
                    onclick={() => router.flushAll()}
                    class={buttonClass({
                        variant: 'ghost',
                        size: 'icon',
                        class: 'hidden sm:inline-flex',
                    })}
                    aria-label="Log out"
                    title="Log out"
                >
                    <LogOut class="size-[19px]" />
                </Link>
            {/if}
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
        <div
            class="absolute inset-x-0 border-b bg-background shadow-lg"
            role="presentation"
            onmouseenter={openMega}
            onmouseleave={scheduleCloseMega}
            transition:panelReveal
        >
            <div class="container-wide py-7">
                {#if !expandedCategory}
                    {#key megaOpenId}
                        <div class="grid grid-cols-4 gap-5">
                            {#each $categories as c, i (c.id)}
                                <button
                                    type="button"
                                    class="mega-slide group flex items-center gap-3 text-left transition-transform duration-200 ease-out hover:-translate-y-0.5 hover:text-primary"
                                    style="--slide-delay: {80 + i * 80}ms"
                                    onclick={() => selectCategory(c)}
                                >
                                    <img
                                        src={c.image}
                                        alt=""
                                        class="h-14 w-14 rounded-md bg-secondary object-contain transition-transform duration-200 ease-out group-hover:scale-105"
                                    />
                                    <span class="text-xs">{c.name}</span>
                                    {#if c.subcategories.length}
                                        <ChevronDown class="-ml-1 size-3 -rotate-90 text-muted-foreground" />
                                    {/if}
                                </button>
                            {/each}
                        </div>
                    {/key}
                {:else}
                    {@const activeCategory = expandedCategory}
                    <div in:viewSwap>
                        <button
                            type="button"
                            class="mb-5 flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-primary"
                            onclick={() => (expandedCategory = null)}
                        >
                            <ChevronLeft class="size-3" />
                            Back to categories
                        </button>
                        {#if activeCategory.subcategories.length}
                            <div class="grid grid-cols-4 gap-5">
                                {#each activeCategory.subcategories as s, i (s.id)}
                                    <div class="mega-slide" style="--slide-delay: {60 + i * 80}ms">
                                        <Link
                                            href={catalogue.url({
                                                query: { category: activeCategory.slug, subcategory: s.slug },
                                            })}
                                            onclick={() => {
                                                mega = false;
                                                expandedCategory = null;
                                            }}
                                            class="group flex items-center gap-3 transition-transform duration-200 ease-out hover:-translate-y-0.5 hover:text-primary"
                                        >
                                            <img
                                                src={s.image}
                                                alt=""
                                                class="h-14 w-14 rounded-md bg-secondary object-contain transition-transform duration-200 ease-out group-hover:scale-105"
                                            />
                                            <span class="text-xs">{s.name}</span>
                                        </Link>
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <p class="text-xs text-muted-foreground">
                                No subcategories yet in {activeCategory.name}.
                            </p>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</header>

{#if mega}
    <button
        type="button"
        class="fixed inset-0 z-30 cursor-default bg-black/10 backdrop-blur-[6px]"
        aria-label="Close category menu"
        transition:fade={{ duration: 280, easing: cubicOut }}
        onclick={() => {
            mega = false;
            expandedCategory = null;
        }}
    ></button>
{/if}

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
        {#if !user}
            <Link
                href={login()}
                onclick={() => (mobile = false)}
                class="flex justify-between border-b py-4"
            >
                Log in
                <UserRound class="size-4 text-primary" />
            </Link>
        {:else}
            {#if isAdmin}
                <Link
                    href={dashboard()}
                    onclick={() => (mobile = false)}
                    class="flex justify-between border-b py-4"
                >
                    Dashboard
                    <LayoutDashboard class="size-4 text-primary" />
                </Link>
            {/if}
            <Link
                href={logout()}
                as="button"
                onclick={() => {
                    mobile = false;
                    router.flushAll();
                }}
                class="flex justify-between border-b py-4 text-left"
            >
                Log out
                <LogOut class="size-4 text-primary" />
            </Link>
        {/if}
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

<style>
    /* CSS keyframe animations replay on every DOM insertion, unlike Svelte's
       in: transitions which can fail to re-fire in runes mode. Items glide in
       from the left with a slow, premium easing curve. Using the standalone
       `translate` property avoids conflicts with Tailwind's hover:transform
       utilities after the animation ends. */
    .mega-slide {
        animation: mega-slide-in 1.1s cubic-bezier(0.23, 1, 0.32, 1) backwards;
        animation-delay: var(--slide-delay, 0ms);
    }

    @keyframes mega-slide-in {
        from {
            opacity: 0;
            translate: -52px;
        }
        to {
            opacity: 1;
            translate: 0px;
        }
    }
</style>
