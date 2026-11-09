<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Phone from '@lucide/svelte/icons/phone';
    import { resolvePhone } from '@/lib/contact';
    import { contact } from '@/routes';
    import { buttonClass } from './button';

    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
</script>

<section class="border-t border-primary/10 bg-accent">
    <div
        class="container-wide flex flex-col justify-between gap-8 py-14 lg:flex-row lg:items-center lg:py-16"
    >
        <div>
            <p class="eyebrow mb-3">Your next step, made simpler</p>
            <h2 class="max-w-[610px] text-[27px] leading-snug lg:text-[31px]">
                Looking for the right equipment
                <br class="hidden sm:block" /> for your practice?
            </h2>
            <p class="mt-4 max-w-[530px] text-xs leading-7 text-muted-foreground">
                Our team can help you explore suitable products, understand
                specifications, and discuss pricing.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a
                href="tel:{phone.tel}"
                class={buttonClass({ class: 'h-11 px-5 text-[11px] shadow-none' })}
            >
                <Phone class="size-3.5" />
                Call Our Sales Team
            </a>
            <Link
                href={contact.url()}
                class={buttonClass({
                    variant: 'outline',
                    class: 'h-11 bg-transparent px-5 text-[11px]',
                })}
            >
                Contact Us
                <ArrowUpRight class="size-3.5" />
            </Link>
        </div>
    </div>
</section>
