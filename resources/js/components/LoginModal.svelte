<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import GoogleSignInButton from '@/components/GoogleSignInButton.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import Modal from '@/components/storefront/Modal.svelte';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import type { LoginPromptProduct } from '@/lib/login-prompt.svelte';
    import { register } from '@/routes';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';

    let {
        open = $bindable(false),
        loginMode,
        description = 'Sign in to view full product details.',
        intended = null,
        product = null,
    }: {
        open?: boolean;
        loginMode?: 'email' | 'phone' | 'both';
        description?: string;
        /** Path to go to after signing in (otherwise the server decides). */
        intended?: string | null;
        /** The product the visitor clicked, previewed at the top of the modal. */
        product?: LoginPromptProduct | null;
    } = $props();

    const mode = $derived(loginMode ?? page.props.loginMode ?? 'both');
    const identifierLabel = $derived(
        mode === 'email'
            ? 'Email address'
            : mode === 'phone'
              ? 'Phone number'
              : 'Email or phone',
    );
    const identifierPlaceholder = $derived(
        mode === 'email'
            ? 'you@example.com'
            : mode === 'phone'
              ? '01XXXXXXXXX'
              : 'you@example.com or 01XXXXXXXXX',
    );

    /** Staggers each block's entrance: `style={rise(n)}`. */
    const rise = (index: number) => `--rise-index: ${index}`;
    const close = () => (open = false);
</script>

<Modal
    bind:open
    premium
    title="Welcome back"
    {description}
    class="login-modal w-[calc(100%-2rem)] max-w-[400px] gap-0 overflow-hidden rounded-2xl p-7 sm:rounded-2xl sm:p-8"
>
    {#snippet header({ titleId, descriptionId })}
        <div class="glow" aria-hidden="true"></div>
        <div class="relative flex flex-col items-center text-center">
            <!-- Same logo file as the storefront header (Wordmark.svelte). -->
            <img
                src="/logo.png"
                alt="{page.props.siteSettings.name} logo"
                width={1448}
                height={596}
                class="logo rise h-12 w-auto max-w-[180px] object-contain"
                style={rise(0)}
            />
            <h2 id={titleId} class="rise mt-4 text-xl leading-tight font-semibold" style={rise(1)}>
                Welcome back
            </h2>
            <p
                id={descriptionId}
                class="rise mt-1.5 text-sm leading-relaxed text-muted-foreground"
                style={rise(2)}
            >
                {product ? 'Sign in to see the full details of' : description}
            </p>

            {#if product}
                <div
                    class="product-peek rise mt-4 flex w-full items-center gap-3 rounded-xl border bg-secondary/40 p-2.5 text-left"
                    style={rise(3)}
                >
                    {#if product.image}
                        <img
                            src={product.image}
                            alt=""
                            class="size-12 shrink-0 rounded-lg bg-background object-contain"
                        />
                    {/if}
                    <span class="line-clamp-2 min-w-0 text-sm font-medium">{product.name}</span>
                </div>
            {/if}
        </div>
    {/snippet}

    <div class="rise relative" style={rise(4)}>
        <GoogleSignInButton class="mt-7" {intended} />
    </div>

    <Form
        {...store.form()}
        resetOnSuccess={['password']}
        onSuccess={close}
        class="relative mt-7 grid gap-5"
    >
        {#snippet children({ errors, processing })}
            {#if intended}
                <input type="hidden" name="intended" value={intended} />
            {/if}

            <div class="rise grid gap-2" style={rise(5)}>
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

            <div class="rise grid gap-2" style={rise(6)}>
                <div class="flex items-center justify-between gap-3">
                    <Label for="modal_password" class="text-[13px]">
                        Password
                    </Label>
                    <Link
                        href={request()}
                        onclick={close}
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

            <div class="rise" style={rise(7)}>
                <Label
                    for="modal_remember"
                    class="flex w-fit items-center gap-2.5 text-[13px] font-normal text-muted-foreground"
                >
                    <Checkbox id="modal_remember" name="remember" />
                    Remember me
                </Label>
            </div>

            <div class="rise" style={rise(8)}>
                <Button
                    type="submit"
                    class="mt-1 h-10 w-full"
                    disabled={processing}
                    data-test="modal-login-button"
                >
                    {#if processing}<Spinner />{/if}
                    Log in
                </Button>
            </div>
        {/snippet}
    </Form>

    <p class="rise relative mt-6 border-t pt-5 text-center text-sm text-muted-foreground" style={rise(9)}>
        New here?
        <Link
            href={register()}
            onclick={close}
            class="font-medium text-primary hover:underline"
        >
            Create an account
        </Link>
    </p>
</Modal>

<style>
    /* Each block glides up in turn after the panel lands. CSS keyframes replay on
       every opening (Svelte intro transitions can fail to re-fire in runes mode). */
    .rise {
        animation: login-rise 560ms cubic-bezier(0.16, 1, 0.3, 1) both;
        animation-delay: calc(140ms + var(--rise-index, 0) * 45ms);
    }

    /* A soft wash of brand colour behind the logo. */
    .glow {
        position: absolute;
        inset: -40% -20% auto;
        height: 260px;
        background: radial-gradient(closest-side, color-mix(in oklab, var(--primary) 16%, transparent), transparent);
        pointer-events: none;
        animation: login-glow 1200ms ease-out both;
    }

    /* The logo settles in from slightly larger, out of a soft blur. */
    .logo {
        animation-name: login-logo;
        animation-duration: 760ms;
    }

    /* A single light sweep across the product preview. */
    .product-peek {
        position: relative;
        overflow: hidden;
    }

    .product-peek::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(105deg, transparent 35%, rgb(255 255 255 / 0.55) 50%, transparent 65%);
        transform: translateX(-100%);
        animation: login-sheen 1100ms ease-in-out 700ms both;
        pointer-events: none;
    }

    @keyframes login-rise {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
    }

    @keyframes login-glow {
        from {
            opacity: 0;
            transform: scale(0.6);
        }
    }

    @keyframes login-logo {
        from {
            opacity: 0;
            transform: scale(1.12);
            filter: blur(4px);
        }
    }

    @keyframes login-sheen {
        to {
            transform: translateX(100%);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .rise,
        .glow,
        .product-peek::after {
            animation: none;
        }

        .product-peek::after {
            display: none;
        }
    }
</style>
