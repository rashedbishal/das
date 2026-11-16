<script lang="ts">
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import { tick } from 'svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

    /**
     * Lists every validation error at the top of a long form (e.g. in a scrolling
     * modal) and scrolls itself into view when a submit fails, so the admin sees
     * which fields need attention even when they are off-screen.
     */
    let { errors }: { errors: Record<string, string | undefined> } = $props();

    let root = $state<HTMLElement | null>(null);
    const messages = $derived([...new Set(Object.values(errors).filter((message): message is string => !!message))]);

    $effect(() => {
        if (messages.length > 0) {
            tick().then(() => root?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        }
    });
</script>

{#if messages.length > 0}
    <div bind:this={root} class="scroll-mt-4">
        <Alert variant="destructive">
            <CircleAlert />
            <AlertTitle>
                Please fix {messages.length === 1 ? 'this field' : `these ${messages.length} fields`} and save again
            </AlertTitle>
            <AlertDescription>
                <ul class="list-disc space-y-0.5 pl-4">
                    {#each messages as message (message)}
                        <li>{message}</li>
                    {/each}
                </ul>
            </AlertDescription>
        </Alert>
    </div>
{/if}
