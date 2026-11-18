<script module lang="ts">
    export type VariantDetail = {
        id: number;
        name: string;
        sku: string | null;
        price: string | null;
        stock_status: string;
        is_active: boolean;
    };

</script>

<script lang="ts">
    import Plus from '@lucide/svelte/icons/plus';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { fieldLimits } from '@/lib/field-limits';
    import RowControls from './RowControls.svelte';
    import { keyed, moveRow } from './rows';

    let {
        initial = [],
        stockStatusOptions,
        errors,
    }: {
        initial?: VariantDetail[];
        stockStatusOptions: { value: string; label: string }[];
        errors: Record<string, string>;
    } = $props();

    const blank = () =>
        keyed({
            id: null as number | null,
            name: '',
            sku: '',
            price: '' as string | number,
            stock_status: 'on_order',
            is_active: true,
        });

    // Starts empty: most products have no options.
    let rows = $state(
        untrack(() =>
            initial.map((variant) =>
                keyed({
                    id: variant.id as number | null,
                    name: variant.name,
                    sku: variant.sku ?? '',
                    price: (variant.price ?? '') as string | number,
                    stock_status: variant.stock_status,
                    is_active: variant.is_active,
                }),
            ),
        ),
    );

    const error = (index: number, field: string) => errors[`variants.${index}.${field}`];
</script>

<fieldset class="grid gap-3">
    <legend class="mb-1 text-sm font-medium">Options</legend>
    <p class="-mt-1 text-xs text-muted-foreground">
        Variants customers can ask about, such as sizes or pin types, listed on the product page in this order.
        A price is only shown when the product's price is shown. Inactive options are hidden from customers.
    </p>
    {#if rows.length === 0}
        <!-- Sends an empty list so saving removes every option. -->
        <input type="hidden" name="variants" value="" />
    {/if}
    {#each rows as variant, index (variant.key)}
        <div class="grid gap-3 rounded-md border p-3">
            {#if variant.id !== null}
                <input type="hidden" name="variants[{index}][id]" value={variant.id} />
            {/if}
            <div class="grid items-start gap-3 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label for="variant-name-{variant.key}">Name</Label>
                    <LimitedInput
                        id="variant-name-{variant.key}"
                        name="variants[{index}][name]"
                        limit={fieldLimits.product.variant_name}
                        placeholder="e.g. SS Pins"
                        required
                        bind:value={variant.name}
                    />
                    <InputError message={error(index, 'name')} />
                </div>
                <div class="grid gap-1">
                    <Label for="variant-sku-{variant.key}">SKU</Label>
                    <LimitedInput
                        id="variant-sku-{variant.key}"
                        name="variants[{index}][sku]"
                        limit={fieldLimits.product.sku}
                        placeholder="Optional"
                        bind:value={variant.sku}
                    />
                    <InputError message={error(index, 'sku')} />
                </div>
                <div class="grid gap-1">
                    <Label for="variant-price-{variant.key}">Price</Label>
                    <Input
                        id="variant-price-{variant.key}"
                        name="variants[{index}][price]"
                        type="number"
                        step="0.01"
                        min="0"
                        max={fieldLimits.number.price}
                        placeholder="Contact for price"
                        bind:value={variant.price}
                    />
                    <InputError message={error(index, 'price')} />
                </div>
                <div class="grid gap-1">
                    <Label for="variant-stock-{variant.key}">Stock status</Label>
                    <FormSelect
                        id="variant-stock-{variant.key}"
                        name="variants[{index}][stock_status]"
                        options={stockStatusOptions}
                        bind:value={variant.stock_status}
                    />
                    <InputError message={error(index, 'stock_status')} />
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <input type="hidden" name="variants[{index}][is_active]" value={variant.is_active ? '1' : '0'} />
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="accent-primary" bind:checked={variant.is_active} />
                    Show to customers
                </label>
                <RowControls
                    {index}
                    count={rows.length}
                    label="option"
                    onmove={(offset) => moveRow(rows, index, offset)}
                    onremove={() => rows.splice(index, 1)}
                />
            </div>
        </div>
    {/each}
    <div>
        <Button type="button" variant="outline" size="sm" onclick={() => rows.push(blank())}>
            <Plus class="size-4" />
            Add option
        </Button>
    </div>
    <InputError message={errors.variants} />
</fieldset>
