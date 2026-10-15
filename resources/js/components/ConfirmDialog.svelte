<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';

    let {
        open = $bindable(false),
        title,
        description = '',
        confirmLabel = 'Delete',
        onConfirm,
    }: {
        open?: boolean;
        title: string;
        description?: string;
        confirmLabel?: string;
        onConfirm: () => void;
    } = $props();

    function confirm() {
        onConfirm();
        open = false;
    }
</script>

<Dialog bind:open>
    <DialogContent>
        <DialogTitle>{title}</DialogTitle>
        {#if description}
            <p class="text-sm text-muted-foreground">{description}</p>
        {/if}
        <DialogFooter>
            <Button type="button" variant="outline" onclick={() => (open = false)}>Cancel</Button>
            <Button type="button" variant="destructive" onclick={confirm}>{confirmLabel}</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
