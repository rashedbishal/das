<script module lang="ts">
    export const layout = {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
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
              : 'Email or phone number',
    );
    const identifierPlaceholder = $derived(
        loginMode === 'email'
            ? 'email@example.com'
            : loginMode === 'phone'
              ? '01XXXXXXXXX'
              : 'email@example.com or 01XXXXXXXXX',
    );
</script>

<AppHead title="Log in" />

{#if status}
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        {status}
    </div>
{/if}


<Form
    {...store.form()}
    resetOnSuccess={['password']}
    class="flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{identifierLabel}</Label>
                <Input
                    id="email"
                    type="text"
                    name="email"
                    required
                    autocomplete="username"
                    placeholder={identifierPlaceholder}
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    {#if canResetPassword}
                        <TextLink href={request()} class="text-sm">
                            Forgot your password?
                        </TextLink>
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}<Spinner />{/if}
                Log in
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Don't have an account?
            <TextLink href={register()}>Sign up</TextLink>
        </div>
    {/snippet}
</Form>
