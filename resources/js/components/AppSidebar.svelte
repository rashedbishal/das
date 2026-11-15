<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import History from '@lucide/svelte/icons/history';
    import Images from '@lucide/svelte/icons/images';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Mail from '@lucide/svelte/icons/mail';
    import Package from '@lucide/svelte/icons/package';
    import SearchCheck from '@lucide/svelte/icons/search-check';
    import Settings from '@lucide/svelte/icons/settings';
    import Tags from '@lucide/svelte/icons/tags';
    import Users from '@lucide/svelte/icons/users';
    import Warehouse from '@lucide/svelte/icons/warehouse';
    import type { Snippet } from 'svelte';
    import { onMount } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
        useSidebar,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const { setOpenMobile } = useSidebar();

    onMount(() => router.on('start', () => setOpenMobile(false)));

    const isAdmin = $derived(page.props.auth.user?.user_type === 'admin');

    const mainNavItems: NavItem[] = $derived([
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(isAdmin
            ? [
                  { title: 'Products', href: '/admin/products', icon: Package },
                  { title: 'Stock Management', href: '/admin/stock', icon: Warehouse },
                  { title: 'Category Management', href: '/admin/categories', icon: Tags },
                  { title: 'Subcategories', href: '/admin/subcategories', icon: Tags },
                  { title: 'Brands', href: '/admin/brands', icon: Tags },
                  { title: 'Tags', href: '/admin/tags', icon: Tags },
                  { title: 'Homepage Banners', href: '/admin/homepage-sections', icon: Images },
                  { title: 'SEO Health', href: '/admin/seo', icon: SearchCheck },
                  { title: 'Enquiries', href: '/admin/enquiries', icon: Mail },
                  { title: 'Users & Roles', href: '/admin/users', icon: Users },
                  { title: 'Activity Log', href: '/admin/activity-log', icon: History },
                  { title: 'Settings', href: '/admin/settings', icon: Settings },
              ]
            : []),
    ]);
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
