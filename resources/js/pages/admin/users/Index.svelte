<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Users & Roles', href: '/admin/users' }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { ApiError, apiFetch } from '@/lib/api';
    import * as adminsApi from '@/routes/api/v1/admin/admins';
    import * as customersApi from '@/routes/api/v1/admin/customers';
    import * as rolesApi from '@/routes/api/v1/admin/roles';

    type Role = { id: number; name: string; slug: string; is_system: boolean; permissions: { id: number; slug: string; name: string }[] };
    type Permission = { id: number; slug: string; name: string; group: string };
    type AdminUser = { id: number; name: string; email: string | null; is_active: boolean; role: Role | null };
    type Customer = { id: number; name: string; email: string | null; phone: string | null; is_active: boolean };

    let tab = $state<'admins' | 'roles' | 'customers'>('admins');

    let admins = $state<AdminUser[]>([]);
    let roles = $state<Role[]>([]);
    let permissions = $state<Permission[]>([]);
    let customers = $state<Customer[]>([]);
    let error = $state<string | null>(null);

    let adminSearch = $state('');
    let roleSearch = $state('');
    let customerSearch = $state('');

    const filteredAdmins = $derived(
        admins.filter(
            (admin) =>
                admin.name.toLowerCase().includes(adminSearch.toLowerCase()) ||
                (admin.email ?? '').toLowerCase().includes(adminSearch.toLowerCase()),
        ),
    );
    const filteredRoles = $derived(roles.filter((role) => role.name.toLowerCase().includes(roleSearch.toLowerCase())));
    const filteredCustomers = $derived(
        customers.filter(
            (customer) =>
                customer.name.toLowerCase().includes(customerSearch.toLowerCase()) ||
                (customer.email ?? '').toLowerCase().includes(customerSearch.toLowerCase()) ||
                (customer.phone ?? '').toLowerCase().includes(customerSearch.toLowerCase()),
        ),
    );

    let createAdminOpen = $state(false);
    let createRoleOpen = $state(false);
    let deleteRoleTarget = $state<Role | null>(null);
    let deleteRoleOpen = $state(false);

    let newAdmin = $state({ name: '', email: '', password: '', role_id: '' });
    let newRole = $state({ name: '', permissions: [] as string[] });

    async function loadAdmins() {
        const result = await apiFetch<{ data: AdminUser[] }>(adminsApi.index.url());
        admins = result?.data ?? [];
    }

    async function loadRoles() {
        const [rolesResult, permissionsResult] = await Promise.all([
            apiFetch<{ data: Role[] }>(rolesApi.index.url()),
            apiFetch<{ data: Permission[] }>(rolesApi.permissions.url()),
        ]);
        roles = rolesResult?.data ?? [];
        permissions = permissionsResult?.data ?? [];
    }

    async function loadCustomers() {
        const result = await apiFetch<{ data: Customer[] }>(customersApi.index.url());
        customers = result?.data ?? [];
    }

    $effect(() => {
        error = null;
        if (tab === 'admins') {
            loadAdmins().catch((e) => (error = e instanceof Error ? e.message : 'Failed to load'));
            if (roles.length === 0) loadRoles().catch(() => {});
        } else if (tab === 'roles') {
            loadRoles().catch((e) => (error = e instanceof Error ? e.message : 'Failed to load'));
        } else if (tab === 'customers') {
            loadCustomers().catch((e) => (error = e instanceof Error ? e.message : 'Failed to load'));
        }
    });

    async function createAdmin(event: SubmitEvent) {
        event.preventDefault();
        error = null;
        try {
            await apiFetch(adminsApi.store.url(), {
                method: 'POST',
                body: JSON.stringify({ ...newAdmin, role_id: Number(newAdmin.role_id) }),
            });
            newAdmin = { name: '', email: '', password: '', role_id: '' };
            createAdminOpen = false;
            await loadAdmins();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Failed to create admin';
        }
    }

    async function toggleAdminActive(admin: AdminUser) {
        error = null;
        try {
            const action = admin.is_active ? adminsApi.deactivate : adminsApi.reactivate;
            await apiFetch(action.url(admin.id), { method: 'POST' });
            await loadAdmins();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Action failed';
        }
    }

    function togglePermission(slug: string) {
        newRole.permissions = newRole.permissions.includes(slug)
            ? newRole.permissions.filter((p) => p !== slug)
            : [...newRole.permissions, slug];
    }

    async function createRole(event: SubmitEvent) {
        event.preventDefault();
        error = null;
        try {
            await apiFetch(rolesApi.store.url(), {
                method: 'POST',
                body: JSON.stringify(newRole),
            });
            newRole = { name: '', permissions: [] };
            createRoleOpen = false;
            await loadRoles();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Failed to create role';
        }
    }

    function askDeleteRole(role: Role) {
        deleteRoleTarget = role;
        deleteRoleOpen = true;
    }

    async function confirmDeleteRole() {
        if (!deleteRoleTarget) return;
        error = null;
        try {
            await apiFetch(rolesApi.destroy.url(deleteRoleTarget.id), { method: 'DELETE' });
            await loadRoles();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Failed to delete role';
        }
    }

    async function toggleCustomerActive(customer: Customer) {
        error = null;
        try {
            const action = customer.is_active ? customersApi.deactivate : customersApi.reactivate;
            await apiFetch(action.url(customer.id), { method: 'POST' });
            await loadCustomers();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Action failed';
        }
    }
</script>

<AppHead title="Users & Roles" />

<div class="w-full p-4">
    <div class="mb-6 flex gap-2 overflow-x-auto border-b">
        {#each [['admins', 'Admins'], ['roles', 'Roles'], ['customers', 'Customers']] as [value, label] (value)}
            <button
                class="shrink-0 border-b-2 px-3 py-2 text-sm {tab === value ? 'border-primary font-medium' : 'border-transparent text-muted-foreground'}"
                onclick={() => (tab = value as typeof tab)}
            >
                {label}
            </button>
        {/each}
    </div>

    {#if error}
        <p class="mb-4 rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</p>
    {/if}

    {#if tab === 'admins'}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Admins</h1>
            <Button onclick={() => (createAdminOpen = true)}>Create admin</Button>
        </div>

        <Input bind:value={adminSearch} placeholder="Search admins..." class="mb-4 sm:max-w-sm" />

        <ResponsiveTable
            items={filteredAdmins}
            key={(admin) => admin.id}
            columns={[
                { key: 'name', label: 'Name' },
                { key: 'email', label: 'Email' },
                { key: 'role', label: 'Role' },
                { key: 'status', label: 'Status' },
            ]}
            empty="No admins found."
        >
            {#snippet cell(admin, column)}
                {#if column.key === 'name'}
                    {admin.name}
                {:else if column.key === 'email'}
                    {admin.email ?? '—'}
                {:else if column.key === 'role'}
                    {admin.role?.name ?? '—'}
                {:else}
                    {@render statusBadge(admin.is_active)}
                {/if}
            {/snippet}
            {#snippet actions(admin)}
                {#if !admin.role?.is_system}
                    <Button variant="ghost" size="sm" onclick={() => toggleAdminActive(admin)}>
                        {admin.is_active ? 'Deactivate' : 'Reactivate'}
                    </Button>
                {/if}
            {/snippet}
        </ResponsiveTable>
    {:else if tab === 'roles'}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Roles</h1>
            <Button onclick={() => (createRoleOpen = true)}>Create role</Button>
        </div>

        <Input bind:value={roleSearch} placeholder="Search roles..." class="mb-4 sm:max-w-sm" />

        <div class="space-y-3">
            {#each filteredRoles as role (role.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium">{role.name}</p>
                            <p class="text-xs text-muted-foreground">
                                {role.permissions.map((p) => p.name).join(', ') || 'No permissions'}
                            </p>
                        </div>
                        {#if !role.is_system}
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                onclick={() => askDeleteRole(role)}
                            >
                                Delete
                            </Button>
                        {/if}
                    </div>
                </div>
            {:else}
                <p class="text-muted-foreground">No roles found.</p>
            {/each}
        </div>
    {:else if tab === 'customers'}
        <h1 class="mb-4 text-xl font-semibold">Customers</h1>

        <Input bind:value={customerSearch} placeholder="Search customers..." class="mb-4 sm:max-w-sm" />

        <ResponsiveTable
            items={filteredCustomers}
            key={(customer) => customer.id}
            columns={[
                { key: 'name', label: 'Name' },
                { key: 'email', label: 'Email' },
                { key: 'phone', label: 'Phone' },
                { key: 'status', label: 'Status' },
            ]}
            empty="No customers found."
        >
            {#snippet cell(customer, column)}
                {#if column.key === 'name'}
                    {customer.name}
                {:else if column.key === 'email'}
                    {customer.email ?? '—'}
                {:else if column.key === 'phone'}
                    {customer.phone ?? '—'}
                {:else}
                    {@render statusBadge(customer.is_active)}
                {/if}
            {/snippet}
            {#snippet actions(customer)}
                <Button variant="ghost" size="sm" onclick={() => toggleCustomerActive(customer)}>
                    {customer.is_active ? 'Deactivate' : 'Reactivate'}
                </Button>
            {/snippet}
        </ResponsiveTable>
    {/if}
</div>

{#snippet statusBadge(isActive: boolean)}
    <Badge variant={isActive ? 'default' : 'secondary'}>
        {isActive ? 'Active' : 'Inactive'}
    </Badge>
{/snippet}

<Dialog bind:open={createAdminOpen}>
    <DialogContent>
        <DialogTitle>Create admin</DialogTitle>
        <form onsubmit={createAdmin} class="space-y-4">
            <div class="grid gap-2">
                <Label for="admin_name">Name</Label>
                <Input id="admin_name" required bind:value={newAdmin.name} />
            </div>
            <div class="grid gap-2">
                <Label for="admin_email">Email</Label>
                <Input id="admin_email" type="email" required bind:value={newAdmin.email} />
            </div>
            <div class="grid gap-2">
                <Label for="admin_password">Password</Label>
                <Input id="admin_password" type="password" required bind:value={newAdmin.password} />
            </div>
            <div class="grid gap-2">
                <Label for="admin_role">Role</Label>
                <FormSelect
                    id="admin_role"
                    bind:value={newAdmin.role_id}
                    placeholder="Select a role"
                    options={roles
                        .filter((role) => !role.is_system)
                        .map((role) => ({ value: String(role.id), label: role.name }))}
                />
            </div>
            <DialogFooter>
                <Button type="button" variant="outline" onclick={() => (createAdminOpen = false)}>Cancel</Button>
                <Button type="submit">Create admin</Button>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>

<Dialog bind:open={createRoleOpen}>
    <DialogContent>
        <DialogTitle>Create role</DialogTitle>
        <form onsubmit={createRole} class="space-y-4">
            <div class="grid gap-2">
                <Label for="role_name">Name</Label>
                <Input id="role_name" required bind:value={newRole.name} />
            </div>
            <fieldset class="grid max-h-64 gap-1 overflow-y-auto rounded-md border p-3 text-sm">
                <legend class="mb-1 text-sm font-medium">Permissions</legend>
                {#each permissions as permission (permission.id)}
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={newRole.permissions.includes(permission.slug)}
                            onchange={() => togglePermission(permission.slug)}
                        />
                        {permission.name}
                    </label>
                {/each}
            </fieldset>
            <DialogFooter>
                <Button type="button" variant="outline" onclick={() => (createRoleOpen = false)}>Cancel</Button>
                <Button type="submit">Create role</Button>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteRoleOpen}
    title="Delete role?"
    description={deleteRoleTarget ? `This will permanently delete "${deleteRoleTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteRole}
/>
