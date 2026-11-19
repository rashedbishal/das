<script module lang="ts">
    export const layout = {
        title: 'Admin sign in',
        description: 'Sign in to manage the catalog.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { home } from '@/routes';
    import { store } from '@/routes/admin/login';
</script>

<AppHead title="Admin sign in" />

<Form {...store.form()} resetOnSuccess={['password']} class="grid gap-5">
    {#snippet children({ errors, processing })}
        <div class="grid gap-2">
            <Label for="email" class="text-[13px]">Email address</Label>
            <Input
                id="email"
                type="text"
                name="email"
                required
                autocomplete="username"
                placeholder="admin@example.com"
                class="h-10"
            />
            <InputError message={errors.email} />
        </div>

        <div class="grid gap-2">
            <Label for="password" class="text-[13px]">Password</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="h-10"
            />
            <InputError message={errors.password} />
        </div>

        <Button
            type="submit"
            class="mt-1 h-10 w-full"
            disabled={processing}
            data-test="admin-login-button"
        >
            {#if processing}<Spinner />{/if}
            Sign in
        </Button>

        <p
            class="mt-1 border-t pt-5 text-center text-sm text-muted-foreground"
        >
            <TextLink href={home()}>Back to the store</TextLink>
        </p>
    {/snippet}
</Form>
