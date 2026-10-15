<script lang="ts">
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Button from './Button.svelte';
    import EnquiryForm from './EnquiryForm.svelte';
    import Modal from './Modal.svelte';

    let { product }: { product: string } = $props();

    let open = $state(false);
</script>

<Button variant="outline" class="h-12 px-6" onclick={() => (open = true)}>
    Request a Quote
    <ArrowUpRight />
</Button>
<Modal
    bind:open
    title="Let’s talk about your equipment."
    description="Enquire about {product}. This is a disconnected demonstration form."
    class="max-h-[90dvh] max-w-xl overflow-y-auto"
>
    <EnquiryForm {product} />
</Modal>
