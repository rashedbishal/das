<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Users & Roles', href: '/admin/users' }],
    };
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Power from '@lucide/svelte/icons/power';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
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
    type AdminUser = { id: number; name: string; email: string | null; phone: string | null; is_active: boolean; role: Role | null };
    type Customer = { id: number; name: string; email: string | null; phone: string | null; is_active: boolean };

    const currentUserId = $derived(page.props.auth.user?.id as number | undefined);

    let tab = $state<'admins' | 'roles' | 'customers'>('admins');

    let admins = $state<AdminUser[]>([]);
    let roles = $state<Role[]>([]);
    let permissions = $state<Permission[]>([]);
    let customers = $state<Customer[]>([]);
    let error = $state<string | null>(null);
    let success = $state<string | null>(null);
    let selectedAdminIds = $state<number[]>([]);
    let selectedRoleIds = $state<number[]>([]);
    let selectedCustomerIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let pendingBulkAction = $state<{
        type: 'admins' | 'roles' | 'customers';
        action: 'delete' | 'deactivate' | 'reactivate';
    } | null>(null);

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
    let editAdminOpen = $state(false);
    let editingAdmin = $state<AdminUser | null>(null);
    let deleteAdminTarget = $state<AdminUser | null>(null);
    let deleteAdminOpen = $state(false);

    let newAdmin = $state({ name: '', email: '', password: '', role_id: '' });
    let newRole = $state({ name: '', permissions: [] as string[] });
    let editAdminForm = $state({ name: '', email: '', phone: '', role_id: '', password: '', password_confirmation: '' });

    const isEditingSelf = $derived(editingAdmin !== null && editingAdmin.id === currentUserId);
    const selectableRoleIds = $derived(
        filteredRoles.filter((role) => !role.is_system).map((role) => role.id).slice(0, 100),
    );
    const allRolesSelected = $derived(
        selectableRoleIds.length > 0 && selectableRoleIds.every((id) => selectedRoleIds.includes(id)),
    );

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

    function openEditAdmin(admin: AdminUser) {
        editingAdmin = admin;
        editAdminForm = {
            name: admin.name,
            email: admin.email ?? '',
            phone: admin.phone ?? '',
            role_id: admin.role ? String(admin.role.id) : '',
            password: '',
            password_confirmation: '',
        };
        editAdminOpen = true;
    }

    async function updateAdmin(event: SubmitEvent) {
        event.preventDefault();
        if (!editingAdmin) return;
        error = null;

        if (isEditingSelf && editAdminForm.password && editAdminForm.password !== editAdminForm.password_confirmation) {
            error = 'Password confirmation does not match.';
            return;
        }

        try {
            const payload = isEditingSelf
                ? {
                      name: editAdminForm.name,
                      ...(editAdminForm.password
                          ? { password: editAdminForm.password, password_confirmation: editAdminForm.password_confirmation }
                          : {}),
                  }
                : { name: editAdminForm.name, email: editAdminForm.email, phone: editAdminForm.phone || null, role_id: Number(editAdminForm.role_id) };

            await apiFetch(adminsApi.update.url(editingAdmin.id), {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            editAdminOpen = false;
            editingAdmin = null;
            await loadAdmins();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Failed to update admin';
        }
    }

    function askDeleteAdmin(admin: AdminUser) {
        deleteAdminTarget = admin;
        deleteAdminOpen = true;
    }

    async function confirmDeleteAdmin() {
        if (!deleteAdminTarget) return;
        error = null;
        try {
            await apiFetch(adminsApi.destroy.url(deleteAdminTarget.id), { method: 'DELETE' });
            await loadAdmins();
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Failed to delete admin';
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

    async function runBulkAction(
        type: 'admins' | 'roles' | 'customers',
        action: 'delete' | 'deactivate' | 'reactivate',
    ) {
        if (action === 'delete') {
            pendingBulkAction = { type, action };
            bulkDeleteOpen = true;
            return;
        }

        await confirmBulkAction(type, action);
    }

    async function confirmBulkAction(
        type = pendingBulkAction?.type,
        action = pendingBulkAction?.action,
    ) {
        if (!type || !action) return;

        const ids =
            type === 'admins'
                ? selectedAdminIds
                : type === 'roles'
                  ? selectedRoleIds
                  : selectedCustomerIds;
        const api =
            type === 'admins'
                ? adminsApi
                : type === 'roles'
                  ? rolesApi
                  : customersApi;

        error = null;
        success = null;
        try {
            const result = await apiFetch<{ message: string }>(api.bulk.url(), {
                method: 'POST',
                body: JSON.stringify({ ids, action }),
            });
            success = result?.message ?? 'Bulk action completed.';

            if (type === 'admins') {
                selectedAdminIds = [];
                await loadAdmins();
            } else if (type === 'roles') {
                selectedRoleIds = [];
                await loadRoles();
            } else {
                selectedCustomerIds = [];
                await loadCustomers();
            }

            pendingBulkAction = null;
            bulkDeleteOpen = false;
        } catch (e) {
            error = e instanceof ApiError ? e.message : 'Bulk action failed';
        }
    }
</script>

<AppHead title="Users & Roles" />

<div class="w-full p-4">
    <div class="mb-6 flex gap-2 overflow-x-auto border-b">
        {#each [['admins', 'Admins'], ['roles', 'Roles'], ['customers', 'Customers']] as [value, label] (value)}
            <button
                class="shrink-0 border-b-2 px-3 py-2 text-sm {tab === value ? 'border-primary font-medium' : 'border-transparent text-muted-foreground'}"
                onclick={() => {
                    tab = value as typeof tab;
                    selectedAdminIds = [];
                    selectedRoleIds = [];
                    selectedCustomerIds = [];
                }}
            >
                {label}
            </button>
        {/each}
    </div>

    {#if error}
        <p class="mb-4 rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</p>
    {/if}
    {#if success}
        <p class="mb-4 rounded-md bg-primary/10 p-3 text-sm text-primary" role="status">{success}</p>
    {/if}

    {#if tab === 'admins'}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Admins</h1>
            <Button onclick={() => (createAdminOpen = true)}>Create admin</Button>
        </div>

        <Input bind:value={adminSearch} placeholder="Search admins..." class="mb-4 sm:max-w-sm" />

        <BulkActions count={selectedAdminIds.length} onClear={() => (selectedAdminIds = [])}>
            {#snippet children()}
                <Button size="sm" onclick={() => runBulkAction('admins', 'deactivate')}>Deactivate</Button>
                <Button size="sm" variant="outline" onclick={() => runBulkAction('admins', 'reactivate')}>Reactivate</Button>
                <Button size="sm" variant="destructive" onclick={() => runBulkAction('admins', 'delete')}>Delete</Button>
            {/snippet}
        </BulkActions>

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
            selectedKeys={selectedAdminIds}
            disabledKeys={admins.filter((admin) => admin.id === currentUserId || admin.role?.is_system).map((admin) => admin.id)}
            onSelectionChange={(keys) => (selectedAdminIds = keys.map(Number))}
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
                {#if !admin.role?.is_system || admin.id === currentUserId}
                    <Button variant="ghost" size="sm" aria-label="Edit {admin.name}" onclick={() => openEditAdmin(admin)}>
                        <Pencil class="size-4" />
                    </Button>
                {/if}
                {#if !admin.role?.is_system && admin.id !== currentUserId}
                    <Button
                        variant="ghost"
                        size="sm"
                        aria-label={admin.is_active ? `Deactivate ${admin.name}` : `Reactivate ${admin.name}`}
                        onclick={() => toggleAdminActive(admin)}
                    >
                        {#if admin.is_active}
                            <Power class="size-4" />
                        {:else}
                            <RotateCcw class="size-4" />
                        {/if}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        aria-label="Delete {admin.name}"
                        onclick={() => askDeleteAdmin(admin)}
                    >
                        <Trash2 class="size-4" />
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

        <BulkActions count={selectedRoleIds.length} onClear={() => (selectedRoleIds = [])}>
            {#snippet children()}
                <Button size="sm" variant="destructive" onclick={() => runBulkAction('roles', 'delete')}>Delete</Button>
            {/snippet}
        </BulkActions>

        <label class="mb-3 flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                checked={allRolesSelected}
                disabled={selectableRoleIds.length === 0}
                onchange={(event) => {
                    selectedRoleIds = event.currentTarget.checked
                        ? selectableRoleIds
                        : selectedRoleIds.filter((id) => !selectableRoleIds.includes(id));
                }}
                class="size-4 accent-primary"
            />
            Select up to 100 available roles
        </label>

        <div class="space-y-3">
            {#each filteredRoles as role (role.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            {#if !role.is_system}
                                <input
                                    type="checkbox"
                                    aria-label="Select {role.name}"
                                    checked={selectedRoleIds.includes(role.id)}
                                    disabled={!selectedRoleIds.includes(role.id) && selectedRoleIds.length >= 100}
                                    onchange={(event) => {
                                        selectedRoleIds = event.currentTarget.checked
                                            ? [...selectedRoleIds, role.id]
                                            : selectedRoleIds.filter((id) => id !== role.id);
                                    }}
                                    class="mt-1 size-4 shrink-0 accent-primary"
                                />
                            {/if}
                            <div class="min-w-0">
                            <p class="font-medium">{role.name}</p>
                            <p class="text-xs text-muted-foreground">
                                {role.permissions.map((p) => p.name).join(', ') || 'No permissions'}
                            </p>
                            </div>
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

        <BulkActions count={selectedCustomerIds.length} onClear={() => (selectedCustomerIds = [])}>
            {#snippet children()}
                <Button size="sm" onclick={() => runBulkAction('customers', 'deactivate')}>Deactivate</Button>
                <Button size="sm" variant="outline" onclick={() => runBulkAction('customers', 'reactivate')}>Reactivate</Button>
            {/snippet}
        </BulkActions>

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
            selectedKeys={selectedCustomerIds}
            onSelectionChange={(keys) => (selectedCustomerIds = keys.map(Number))}
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

<Dialog bind:open={editAdminOpen}>
    <DialogContent>
        <DialogTitle>Edit admin</DialogTitle>
        {#if editingAdmin}
            <form onsubmit={updateAdmin} class="space-y-4">
                <div class="grid gap-2">
                    <Label for="edit_admin_name">Name</Label>
                    <Input id="edit_admin_name" required bind:value={editAdminForm.name} />
                </div>
                {#if isEditingSelf}
                    <div class="grid gap-2">
                        <Label for="edit_admin_password">New password</Label>
                        <Input
                            id="edit_admin_password"
                            type="password"
                            placeholder="Leave blank to keep current password"
                            bind:value={editAdminForm.password}
                        />
                    </div>
                    {#if editAdminForm.password}
                        <div class="grid gap-2">
                            <Label for="edit_admin_password_confirmation">Confirm new password</Label>
                            <Input
                                id="edit_admin_password_confirmation"
                                type="password"
                                required
                                bind:value={editAdminForm.password_confirmation}
                            />
                        </div>
                    {/if}
                {:else}
                    <div class="grid gap-2">
                        <Label for="edit_admin_email">Email</Label>
                        <Input id="edit_admin_email" type="email" required bind:value={editAdminForm.email} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit_admin_phone">Phone</Label>
                        <Input id="edit_admin_phone" bind:value={editAdminForm.phone} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit_admin_role">Role</Label>
                        <FormSelect
                            id="edit_admin_role"
                            bind:value={editAdminForm.role_id}
                            placeholder="Select a role"
                            options={roles
                                .filter((role) => !role.is_system)
                                .map((role) => ({ value: String(role.id), label: role.name }))}
                        />
                    </div>
                {/if}
                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (editAdminOpen = false)}>Cancel</Button>
                    <Button type="submit">Save changes</Button>
                </DialogFooter>
            </form>
        {/if}
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

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title={pendingBulkAction?.type === 'roles' ? 'Delete selected roles?' : 'Delete selected admins?'}
    description={`This will permanently delete the selected ${pendingBulkAction?.type === 'roles' ? 'roles' : 'administrators'}. Assigned roles and protected administrator accounts cannot be deleted.`}
    onConfirm={() => confirmBulkAction()}
/>

<ConfirmDialog
    bind:open={deleteAdminOpen}
    title="Delete admin?"
    description={deleteAdminTarget ? `This will permanently delete "${deleteAdminTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteAdmin}
/>
