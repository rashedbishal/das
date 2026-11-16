<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import LockKeyhole from '@lucide/svelte/icons/lock-keyhole';
    import GoogleSignInButton from '@/components/GoogleSignInButton.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import Modal from '@/components/storefront/Modal.svelte';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { register } from '@/routes';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';

    let {
        open = $bindable(false),
        loginMode = 'both',
        description = 'Sign in to view full product details.',
    }: {
        open?: boolean;
        loginMode?: 'email' | 'phone' | 'both';
        description?: string;
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

<Modal
    bind:open
    title="Welcome back"
    {description}
    class="w-[calc(100%-2rem)] max-w-[400px] gap-0 rounded-xl p-7 sm:rounded-xl sm:p-8"
>
    {#snippet header({ titleId, descriptionId })}
        <div class="flex flex-col items-center text-center">
            <span
                class="flex size-11 items-center justify-center rounded-full bg-accent text-primary"
            >
                <LockKeyhole class="size-5" />
            </span>
            <h2 id={titleId} class="mt-4 text-xl leading-tight font-semibold">
                Welcome back
            </h2>
            <p
                id={descriptionId}
                class="mt-1.5 text-sm leading-relaxed text-muted-foreground"
            >
                {description}
            </p>
        </div>
    {/snippet}

    <GoogleSignInButton class="mt-7" />

    <Form
        {...store.form()}
        resetOnSuccess={['password']}
        class="mt-7 grid gap-5"
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="modal_email" class="text-[13px]">
                    {identifierLabel}
                </Label>
                <Input
                    id="modal_email"
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
                    <Label for="modal_password" class="text-[13px]">
                        Password
                    </Label>
                    <Link
                        href={request()}
                        class="text-xs font-medium text-primary hover:underline"
                    >
                        Forgot password?
                    </Link>
                </div>
                <PasswordInput
                    id="modal_password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-10"
                />
                <InputError message={errors.password} />
            </div>

            <Label
                for="modal_remember"
                class="flex w-fit items-center gap-2.5 text-[13px] font-normal text-muted-foreground"
            >
                <Checkbox id="modal_remember" name="remember" />
                Remember me
            </Label>

            <Button
                type="submit"
                class="mt-1 h-10 w-full"
                disabled={processing}
                data-test="modal-login-button"
            >
                {#if processing}<Spinner />{/if}
                Log in
            </Button>
        {/snippet}
    </Form>

    <p class="mt-6 border-t pt-5 text-center text-sm text-muted-foreground">
        New here?
        <Link
            href={register()}
            class="font-medium text-primary hover:underline"
        >
            Create an account
        </Link>
    </p>
</Modal>
