<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import BookOpen from '@lucide/svelte/icons/book-open';
    import FolderGit2 from '@lucide/svelte/icons/folder-git-2';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Package from '@lucide/svelte/icons/package';
    import Settings from '@lucide/svelte/icons/settings';
    import Tags from '@lucide/svelte/icons/tags';
    import Users from '@lucide/svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
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
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

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
                  { title: 'Categories', href: '/admin/categories', icon: Tags },
                  { title: 'Brands', href: '/admin/brands', icon: Tags },
                  { title: 'Tags', href: '/admin/tags', icon: Tags },
                  { title: 'Users & Roles', href: '/admin/users', icon: Users },
                  { title: 'Settings', href: '/admin/settings', icon: Settings },
              ]
            : []),
    ]);

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/svelte-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#svelte',
            icon: BookOpen,
        },
    ];
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
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
