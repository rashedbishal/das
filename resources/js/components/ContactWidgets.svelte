<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import MessageCircle from '@lucide/svelte/icons/message-circle';
    import Send from '@lucide/svelte/icons/send';

    const widgets = $derived(page.props.contactWidgets);
</script>

<div class="fixed right-4 bottom-4 z-50 flex flex-col gap-3">
    {#if widgets?.whatsapp?.enabled && widgets.whatsapp.phone}
        <a
            href={`https://wa.me/${widgets.whatsapp.phone}?text=${encodeURIComponent(widgets.whatsapp.message ?? '')}`}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Chat on WhatsApp"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition-opacity hover:opacity-90"
        >
            <MessageCircle class="size-6" />
        </a>
    {/if}

    {#if widgets?.messenger?.enabled && widgets.messenger.page_url}
        <a
            href={widgets.messenger.page_url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Chat on Messenger"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-[#0084FF] text-white shadow-lg transition-opacity hover:opacity-90"
        >
            <Send class="size-6" />
        </a>
    {/if}
</div>
