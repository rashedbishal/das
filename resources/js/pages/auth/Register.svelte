<script module lang="ts">
    export const layout = {
        title: 'Create an account',
        description: 'Register to view full product details and pricing.',
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
    import GoogleSignInButton from '@/components/GoogleSignInButton.svelte';
    import { login } from '@/routes';
    import { store } from '@/routes/register';

    let {
        passwordRules,
        registrationEnabled = true,
    }: {
        passwordRules: string;
        registrationEnabled?: boolean;
    } = $props();
</script>

<AppHead title="Register" />

{#if !registrationEnabled}
    <p class="text-center text-sm text-muted-foreground">
        Registration is currently disabled. Please contact us directly.
    </p>
    <p class="mt-6 border-t pt-5 text-center text-sm text-muted-foreground">
        Already have an account?
        <TextLink href={login()}>Log in</TextLink>
    </p>
{:else}
    <GoogleSignInButton label="Sign up with Google" class="mb-5" />

    <Form
        {...store.form()}
        resetOnSuccess={['password', 'password_confirmation']}
        class="grid gap-5"
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name" class="text-[13px]">Full name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autocomplete="name"
                    name="name"
                    placeholder="Your name"
                    class="h-10"
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="text-[13px]">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="you@example.com"
                    class="h-10"
                />
                <InputError message={errors.email} />
                <p class="text-xs text-muted-foreground">
                    We’ll email you a 6-digit code to confirm it.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="password" class="text-[13px]">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder="••••••••"
                    passwordrules={passwordRules}
                    class="h-10"
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="text-[13px]">
                    Confirm password
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="••••••••"
                    passwordrules={passwordRules}
                    class="h-10"
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <Button
                type="submit"
                class="mt-1 h-10 w-full"
                disabled={processing}
                data-test="register-user-button"
            >
                {#if processing}<Spinner />{/if}
                Create account
            </Button>

            <p
                class="mt-1 border-t pt-5 text-center text-sm text-muted-foreground"
            >
                Already have an account?
                <TextLink href={login()}>Log in</TextLink>
            </p>
        {/snippet}
    </Form>
{/if}
