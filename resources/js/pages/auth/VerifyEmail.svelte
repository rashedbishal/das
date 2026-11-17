<script module lang="ts">
    export const layout = {
        title: 'Check your email',
        description:
            'Enter the 6-digit code we emailed you to confirm your address.',
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { logout } from '@/routes';
    import { code as verifyCode, send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();

    const email = $derived(page.props.auth.user?.email ?? 'your email address');

    let codeForm = $state<{ submit: () => void } | null>(null);

    /** Keep digits only, and submit as soon as all six are in (typed or pasted). */
    function onCodeInput(event: Event) {
        const input = event.currentTarget as HTMLInputElement;
        input.value = input.value.replace(/\D/g, '').slice(0, 6);

        if (input.value.length === 6) {
            codeForm?.submit();
        }
    }
</script>

<AppHead title="Verify your email" />

<p class="mb-6 text-center text-sm text-muted-foreground">
    We sent a code to <span class="font-medium text-foreground">{email}</span>.
    It expires in 15 minutes.
</p>

{#if status === 'verification-link-sent'}
    <div
        class="mb-5 rounded-md bg-accent px-3 py-2 text-center text-sm font-medium text-primary"
        role="status"
    >
        A new code is on its way. Check your inbox (and spam folder).
    </div>
{/if}

<Form
    {...verifyCode.form()}
    bind:this={codeForm}
    resetOnError={['code']}
    class="grid gap-5"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-2">
            <Label for="code" class="text-[13px]">Verification code</Label>
            <Input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength={6}
                required
                autofocus
                placeholder="••••••"
                oninput={onCodeInput}
                class="h-14 text-center text-2xl font-semibold tracking-[0.5em]"
            />
            <InputError message={errors.code} />
        </div>

        <Button type="submit" class="h-11 w-full" disabled={processing}>
            {#if processing}<Spinner />{/if}
            Verify email
        </Button>
    {/snippet}
</Form>

<div class="mt-6 flex flex-col items-center gap-3 border-t pt-5 text-sm">
    <Form {...send.form()}>
        {#snippet children({ processing })}
            <span class="text-muted-foreground">Didn’t get it?</span>
            <button
                type="submit"
                class="font-medium text-primary underline-offset-4 hover:underline disabled:opacity-60"
                disabled={processing}
            >
                Send a new code
            </button>
        {/snippet}
    </Form>

    <TextLink href={logout()} as="button" class="text-muted-foreground">
        Log out
    </TextLink>
</div>
