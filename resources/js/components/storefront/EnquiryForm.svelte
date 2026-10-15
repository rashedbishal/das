<script lang="ts">
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import MessageSquare from '@lucide/svelte/icons/message-square';
    import Phone from '@lucide/svelte/icons/phone';
    import { business, products } from '@/data/catalogue';
    import Button from './Button.svelte';

    let { product = '' }: { product?: string } = $props();

    let submitted = $state(false);
</script>

{#if submitted}
    <div role="status" class="rounded-md border border-primary/20 bg-accent p-6">
        <MessageSquare class="mb-4 size-7 text-primary" />
        <h3 class="text-lg">This demo enquiry has not been sent.</h3>
        <p class="mt-3 text-xs leading-7 text-muted-foreground">
            No information has been saved or delivered. To contact a real
            business, replace these demo details first.
        </p>
        <div class="mt-5 flex flex-col gap-3 text-sm">
            <a
                class="flex items-center gap-2 text-primary"
                href="tel:{business.telephone}"
            >
                <Phone class="size-4" />
                {business.phone}
            </a>
            <a class="text-primary" href="mailto:{business.email}">
                {business.email}
            </a>
        </div>
        <Button variant="outline" class="mt-6" onclick={() => (submitted = false)}>
            Back to enquiry
        </Button>
    </div>
{:else}
    <form
        onsubmit={(event) => {
            event.preventDefault();
            submitted = true;
        }}
        class="space-y-5"
    >
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="form-label">Your name *</span>
                <input
                    name="name"
                    required
                    autocomplete="name"
                    class="field"
                    placeholder="Full name"
                />
            </label>
            <label>
                <span class="form-label">Clinic or business</span>
                <input
                    name="clinic"
                    autocomplete="organization"
                    class="field"
                    placeholder="Business name (optional)"
                />
            </label>
            <label>
                <span class="form-label">Phone number *</span>
                <input
                    name="phone"
                    required
                    type="tel"
                    autocomplete="tel"
                    class="field"
                    placeholder="Your phone number"
                />
            </label>
            <label>
                <span class="form-label">Email address</span>
                <input
                    name="email"
                    type="email"
                    autocomplete="email"
                    class="field"
                    placeholder="Email (optional)"
                />
            </label>
        </div>
        <label class="block">
            <span class="form-label">Product of interest</span>
            <select name="product" class="field" value={product}>
                <option value="">General equipment enquiry</option>
                {#each products as p (p.id)}
                    <option value={p.name}>{p.name}</option>
                {/each}
            </select>
        </label>
        <label class="block">
            <span class="form-label">How can we help?</span>
            <textarea
                name="message"
                class="field min-h-24"
                placeholder="Tell us a little about your equipment needs…"
                rows={3}
            ></textarea>
        </label>
        <div
            class="border-l-2 border-primary pl-3 text-[11px] leading-6 text-muted-foreground"
        >
            Demo form only. Your information will not be sent or stored. Contact
            details shown are placeholders.
        </div>
        <Button type="submit" class="h-11 px-6">
            Preview enquiry
            <ArrowRight class="size-4" />
        </Button>
    </form>
{/if}
