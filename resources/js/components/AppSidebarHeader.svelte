<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();
</script>

<header
    class="grid h-14 shrink-0 grid-cols-[2.5rem_1fr_2.5rem] items-center gap-2 border-b border-sidebar-border/70 px-3 transition-[width,height] ease-linear md:flex md:h-16 md:px-4 md:group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
>
    <SidebarTrigger class="md:-ml-1" />

    <!-- On mobile the sidebar is an off-canvas sheet, so the brand lives in the header. -->
    <Link
        href={toUrl(dashboard())}
        class="flex min-w-0 items-center gap-1 justify-self-center md:hidden"
        aria-label="Dashboard home"
    >
        <AppLogo />
    </Link>

    {#if breadcrumbs && breadcrumbs.length > 0}
        <div class="hidden min-w-0 md:block">
            <Breadcrumbs {breadcrumbs} />
        </div>
    {/if}
</header>
