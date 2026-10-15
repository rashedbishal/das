<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Users & Roles', href: '/admin/users' }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
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

<div class="p-4">
    <div class="mb-6 flex gap-2 border-b">
        {#each [['admins', 'Admins'], ['roles', 'Roles'], ['customers', 'Customers']] as [value, label] (value)}
            <button
                class="border-b-2 px-3 py-2 text-sm {tab === value ? 'border-primary font-medium' : 'border-transparent text-muted-foreground'}"
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
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Admins</h1>
            <Button onclick={() => (createAdminOpen = true)}>Create admin</Button>
        </div>

        <Input bind:value={adminSearch} placeholder="Search admins..." class="mb-4 max-w-sm" />

        <div class="hidden overflow-x-auto rounded-lg border md:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-2">Name</th>
                        <th class="p-2">Email</th>
                        <th class="p-2">Role</th>
                        <th class="p-2">Status</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    {#each filteredAdmins as admin (admin.id)}
                        <tr class="border-t">
                            <td class="p-2">{admin.name}</td>
                            <td class="p-2">{admin.email}</td>
                            <td class="p-2">{admin.role?.name ?? '—'}</td>
                            <td class="p-2">
                                <Badge variant={admin.is_active ? 'default' : 'secondary'}>
                                    {admin.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="p-2 text-right">
                                {#if !admin.role?.is_system}
                                    <button class="text-primary underline" onclick={() => toggleAdminActive(admin)}>
                                        {admin.is_active ? 'Deactivate' : 'Reactivate'}
                                    </button>
                                {/if}
                            </td>
                        </tr>
                    {:else}
                        <tr><td class="p-4 text-muted-foreground" colspan="5">No admins found.</td></tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            {#each filteredAdmins as admin (admin.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium">{admin.name}</p>
                        <Badge variant={admin.is_active ? 'default' : 'secondary'}>
                            {admin.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    <dl class="mt-2 grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                        <dt>Email</dt>
                        <dd class="text-right text-foreground">{admin.email}</dd>
                        <dt>Role</dt>
                        <dd class="text-right text-foreground">{admin.role?.name ?? '—'}</dd>
                    </dl>
                    {#if !admin.role?.is_system}
                        <div class="mt-3 flex justify-end text-sm">
                            <button class="text-primary underline" onclick={() => toggleAdminActive(admin)}>
                                {admin.is_active ? 'Deactivate' : 'Reactivate'}
                            </button>
                        </div>
                    {/if}
                </div>
            {:else}
                <p class="p-4 text-center text-muted-foreground">No admins found.</p>
            {/each}
        </div>
    {:else if tab === 'roles'}
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Roles</h1>
            <Button onclick={() => (createRoleOpen = true)}>Create role</Button>
        </div>

        <Input bind:value={roleSearch} placeholder="Search roles..." class="mb-4 max-w-sm" />

        <div class="space-y-3">
            {#each filteredRoles as role (role.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{role.name}</p>
                            <p class="text-xs text-muted-foreground">
                                {role.permissions.map((p) => p.name).join(', ') || 'No permissions'}
                            </p>
                        </div>
                        {#if !role.is_system}
                            <button class="text-red-600 underline" onclick={() => askDeleteRole(role)}>Delete</button>
                        {/if}
                    </div>
                </div>
            {:else}
                <p class="text-muted-foreground">No roles found.</p>
            {/each}
        </div>
    {:else if tab === 'customers'}
        <h1 class="mb-4 text-xl font-semibold">Customers</h1>

        <Input bind:value={customerSearch} placeholder="Search customers..." class="mb-4 max-w-sm" />

        <div class="hidden overflow-x-auto rounded-lg border md:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-2">Name</th>
                        <th class="p-2">Email</th>
                        <th class="p-2">Phone</th>
                        <th class="p-2">Status</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    {#each filteredCustomers as customer (customer.id)}
                        <tr class="border-t">
                            <td class="p-2">{customer.name}</td>
                            <td class="p-2">{customer.email ?? '—'}</td>
                            <td class="p-2">{customer.phone ?? '—'}</td>
                            <td class="p-2">
                                <Badge variant={customer.is_active ? 'default' : 'secondary'}>
                                    {customer.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="p-2 text-right">
                                <button class="text-primary underline" onclick={() => toggleCustomerActive(customer)}>
                                    {customer.is_active ? 'Deactivate' : 'Reactivate'}
                                </button>
                            </td>
                        </tr>
                    {:else}
                        <tr><td class="p-4 text-muted-foreground" colspan="5">No customers found.</td></tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            {#each filteredCustomers as customer (customer.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium">{customer.name}</p>
                        <Badge variant={customer.is_active ? 'default' : 'secondary'}>
                            {customer.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    <dl class="mt-2 grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                        <dt>Email</dt>
                        <dd class="text-right text-foreground">{customer.email ?? '—'}</dd>
                        <dt>Phone</dt>
                        <dd class="text-right text-foreground">{customer.phone ?? '—'}</dd>
                    </dl>
                    <div class="mt-3 flex justify-end text-sm">
                        <button class="text-primary underline" onclick={() => toggleCustomerActive(customer)}>
                            {customer.is_active ? 'Deactivate' : 'Reactivate'}
                        </button>
                    </div>
                </div>
            {:else}
                <p class="p-4 text-center text-muted-foreground">No customers found.</p>
            {/each}
        </div>
    {/if}
</div>

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
                <select
                    id="admin_role"
                    bind:value={newAdmin.role_id}
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                >
                    <option value="">Select a role</option>
                    {#each roles.filter((r) => !r.is_system) as role (role.id)}
                        <option value={role.id}>{role.name}</option>
                    {/each}
                </select>
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
