<script lang="ts">
    import Plus from '@lucide/svelte/icons/plus';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import { Button } from '@/components/ui/button';
    import { fieldLimits } from '@/lib/field-limits';
    import RowControls from './RowControls.svelte';
    import { keyed, moveRow } from './rows';

    let {
        initial = [],
        errors,
    }: {
        initial?: string[];
        errors: Record<string, string>;
    } = $props();

    const blank = () => keyed({ text: '' });
    let rows = $state(
        untrack(() => (initial.length ? initial.map((text) => keyed({ text })) : [blank()])),
    );
</script>

<fieldset class="grid gap-3">
    <legend class="mb-1 text-sm font-medium">Highlights</legend>
    <p class="-mt-1 text-xs text-muted-foreground">
        Short selling points shown as a bulleted list on the product page. Rows left empty are ignored.
    </p>
    {#if rows.length === 0}
        <!-- Sends an empty list so saving removes every highlight. -->
        <input type="hidden" name="highlights" value="" />
    {/if}
    {#each rows as row, index (row.key)}
        <div class="grid gap-1">
            <div class="flex items-start gap-1">
                <LimitedInput
                    name="highlights[{index}]"
                    aria-label="Highlight {index + 1}"
                    limit={fieldLimits.product.highlight}
                    placeholder="e.g. Withstands repeated firing cycles"
                    bind:value={row.text}
                />
                <RowControls
                    {index}
                    count={rows.length}
                    label="highlight"
                    onmove={(offset) => moveRow(rows, index, offset)}
                    onremove={() => rows.splice(index, 1)}
                />
            </div>
            <InputError message={errors[`highlights.${index}`]} />
        </div>
    {/each}
    <div>
        <Button type="button" variant="outline" size="sm" onclick={() => rows.push(blank())}>
            <Plus class="size-4" />
            Add highlight
        </Button>
    </div>
    <InputError message={errors.highlights} />
</fieldset>
