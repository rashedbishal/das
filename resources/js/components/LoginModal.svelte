<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { store } from '@/routes/login';

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

<Dialog bind:open>
    <DialogContent>
        <DialogTitle>Log in</DialogTitle>
        <DialogDescription>{description}</DialogDescription>

        <Form {...store.form()} resetOnSuccess={['password']} class="flex flex-col gap-6">
            {#snippet children({ errors, processing })}
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="modal_email">{identifierLabel}</Label>
                        <Input
                            id="modal_email"
                            type="text"
                            name="email"
                            required
                            autocomplete="username"
                            placeholder={identifierPlaceholder}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="modal_password">Password</Label>
                        <PasswordInput
                            id="modal_password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <Label for="modal_remember" class="flex items-center space-x-3">
                        <Checkbox id="modal_remember" name="remember" />
                        <span>Remember me</span>
                    </Label>

                    <Button type="submit" class="w-full" disabled={processing} data-test="modal-login-button">
                        {#if processing}<Spinner />{/if}
                        Log in
                    </Button>
                </div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
