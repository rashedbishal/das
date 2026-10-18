<script module lang="ts">
    export const layout = {
        title: 'Welcome back',
        description: 'Log in to your account to continue.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { register } from '@/routes';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';

    let {
        status = '',
        canResetPassword,
        loginMode = 'both',
    }: {
        status?: string;
        canResetPassword: boolean;
        loginMode?: 'email' | 'phone' | 'both';
    } = $props();

    const identifierLabel = $derived(
        loginMode === 'email'
            ? 'Email address'
            : loginMode === 'phone'
              ? 'Phone number'
              : 'Email or phone',
    );
    const identifierPlaceholder = $derived(
        loginMode === 'email'
            ? 'you@example.com'
            : loginMode === 'phone'
              ? '01XXXXXXXXX'
              : 'you@example.com or 01XXXXXXXXX',
    );
</script>

<AppHead title="Log in" />

{#if status}
    <div
        class="mb-5 rounded-md bg-accent px-3 py-2 text-center text-sm font-medium text-primary"
    >
        {status}
    </div>
{/if}

<Form {...store.form()} resetOnSuccess={['password']} class="grid gap-5">
    {#snippet children({ errors, processing })}
        <div class="grid gap-2">
            <Label for="email" class="text-[13px]">{identifierLabel}</Label>
            <Input
                id="email"
                type="text"
                name="email"
                required
                autocomplete="username"
                placeholder={identifierPlaceholder}
                class="h-10"
            />
            <InputError message={errors.email} />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
                <Label for="password" class="text-[13px]">Password</Label>
                {#if canResetPassword}
                    <TextLink href={request()} class="text-xs">
                        Forgot password?
                    </TextLink>
                {/if}
            </div>
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

        <Label
            for="remember"
            class="flex w-fit items-center gap-2.5 text-[13px] font-normal text-muted-foreground"
        >
            <Checkbox id="remember" name="remember" />
            Remember me
        </Label>

        <Button
            type="submit"
            class="mt-1 h-10 w-full"
            disabled={processing}
            data-test="login-button"
        >
            {#if processing}<Spinner />{/if}
            Log in
        </Button>

        <p
            class="mt-1 border-t pt-5 text-center text-sm text-muted-foreground"
        >
            New here?
            <TextLink href={register()}>Create an account</TextLink>
        </p>
    {/snippet}
</Form>
