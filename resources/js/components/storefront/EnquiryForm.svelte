<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import MessageSquare from '@lucide/svelte/icons/message-square';
    import InputError from '@/components/InputError.svelte';
    import Button from './Button.svelte';
    import ProductPicker from './ProductPicker.svelte';
    import { store as storeEnquiry } from '@/routes/contact/enquiries';

    let { product = '' }: { product?: string } = $props();

    let submitted = $state(false);

</script>

{#if submitted}
    <div role="status" class="rounded-md border border-primary/20 bg-accent p-6">
        <MessageSquare class="mb-4 size-7 text-primary" />
        <h3 class="text-lg">Thank you for reaching out.</h3>
        <p class="mt-3 text-xs leading-7 text-muted-foreground">
            Your enquiry has been received. Our team will be in touch using the
            contact details you provided.
        </p>
        <Button
            variant="outline"
            class="mt-6"
            onclick={() => (submitted = false)}
        >
            Send another enquiry
        </Button>
    </div>
{:else}
    <Form
        {...storeEnquiry.form()}
        class="space-y-5"
        onSuccess={() => (submitted = true)}
    >
        {#snippet children({ errors: formErrors, processing })}
            <!-- Honeypot: hidden from people and assistive tech; bots that fill it are ignored. -->
            <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                <label>
                    Website
                    <input name="website" tabindex="-1" autocomplete="off" />
                </label>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="form-label">Your name *</span>
                    <input
                        name="name"
                        required
                        maxlength="120"
                        autocomplete="name"
                        class="field"
                        placeholder="Full name"
                    />
                    <InputError message={formErrors.name} />
                </label>
                <label>
                    <span class="form-label">Clinic or business</span>
                    <input
                        name="business_name"
                        maxlength="255"
                        autocomplete="organization"
                        class="field"
                        placeholder="Business name (optional)"
                    />
                    <InputError message={formErrors.business_name} />
                </label>
                <label>
                    <span class="form-label">Phone number *</span>
                    <!-- Bangladeshi mobile: 11 digits starting 013–019. Non-digits are removed as you type. -->
                    <input
                        name="phone"
                        required
                        type="tel"
                        inputmode="numeric"
                        maxlength="16"
                        pattern={'01[3-9][0-9]{8}'}
                        title="An 11-digit Bangladeshi mobile number, e.g. 01712345678"
                        autocomplete="tel-national"
                        class="field"
                        placeholder="01XXXXXXXXX"
                        oninput={(event) => {
                            // "+880 1712-345678" (pasted) becomes "01712345678".
                            const digits = event.currentTarget.value.replace(/\D/g, '');
                            event.currentTarget.value = (digits.startsWith('880') ? `0${digits.slice(3)}` : digits).slice(0, 11);
                        }}
                    />
                    <InputError message={formErrors.phone} />
                </label>
                <label>
                    <span class="form-label">Email address</span>
                    <input
                        name="email"
                        type="email"
                        maxlength="255"
                        autocomplete="email"
                        class="field"
                        placeholder="Email (optional)"
                    />
                    <InputError message={formErrors.email} />
                </label>
            </div>
            <div>
                <label class="form-label block" for="enquiry-product">Product of interest</label>
                <ProductPicker id="enquiry-product" name="product_name" value={product} />
                <InputError message={formErrors.product_name} />
            </div>
            <label class="block">
                <span class="form-label">How can we help? *</span>
                <textarea
                    name="message"
                    required
                    maxlength="5000"
                    class="field min-h-24"
                    placeholder="Tell us a little about your equipment needs…"
                    rows={3}
                ></textarea>
                <InputError message={formErrors.message} />
            </label>
            <p class="text-[11px] leading-6 text-muted-foreground">
                Your details and enquiry will be stored for the team to review.
            </p>
            <Button type="submit" class="h-11 px-6" disabled={processing}>
                {processing ? 'Sending…' : 'Send enquiry'}
                <ArrowRight class="size-4" />
            </Button>
        {/snippet}
    </Form>
{/if}
