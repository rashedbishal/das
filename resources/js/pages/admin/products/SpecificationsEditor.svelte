<script lang="ts">
    import Plus from '@lucide/svelte/icons/plus';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { fieldLimits } from '@/lib/field-limits';
    import RowControls from './RowControls.svelte';
    import { keyed, moveRow } from './rows';

    let {
        initial = [],
        errors,
    }: {
        initial?: { name: string; value: string }[];
        errors: Record<string, string>;
    } = $props();

    const blank = () => keyed({ name: '', value: '' });
    let rows = $state(
        untrack(() =>
            initial.length
                ? initial.map((spec) => keyed({ name: spec.name, value: spec.value }))
                : [blank()],
        ),
    );
</script>

<fieldset class="grid gap-3">
    <legend class="mb-1 text-sm font-medium">Technical specifications</legend>
    <p class="-mt-1 text-xs text-muted-foreground">
        Shown as a name/value table on the product page, in this order. Rows left empty are ignored.
    </p>
    {#if rows.length === 0}
        <!-- Sends an empty list so saving removes every specification. -->
        <input type="hidden" name="specifications" value="" />
    {/if}
    {#each rows as spec, index (spec.key)}
        <div class="grid gap-2 rounded-md border p-3 sm:grid-cols-[1fr_1.5fr_auto] sm:items-start sm:border-0 sm:p-0">
            <div class="grid gap-1">
                <Label for="spec-name-{spec.key}" class="sm:sr-only">Name</Label>
                <LimitedInput
                    id="spec-name-{spec.key}"
                    name="specifications[{index}][name]"
                    limit={fieldLimits.product.spec_name}
                    placeholder="e.g. Material"
                    required={spec.value.trim() !== ''}
                    bind:value={spec.name}
                />
                <InputError message={errors[`specifications.${index}.name`]} />
            </div>
            <div class="grid gap-1">
                <Label for="spec-value-{spec.key}" class="sm:sr-only">Value</Label>
                <LimitedInput
                    id="spec-value-{spec.key}"
                    name="specifications[{index}][value]"
                    limit={fieldLimits.product.spec_value}
                    placeholder="e.g. Ceramic"
                    required={spec.name.trim() !== ''}
                    bind:value={spec.value}
                />
                <InputError message={errors[`specifications.${index}.value`]} />
            </div>
            <RowControls
                {index}
                count={rows.length}
                label="specification"
                onmove={(offset) => moveRow(rows, index, offset)}
                onremove={() => rows.splice(index, 1)}
            />
        </div>
    {/each}
    <div>
        <Button type="button" variant="outline" size="sm" onclick={() => rows.push(blank())}>
            <Plus class="size-4" />
            Add specification
        </Button>
    </div>
    <InputError message={errors.specifications} />
</fieldset>
