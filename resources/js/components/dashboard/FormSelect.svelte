<script module lang="ts">
    export type SelectOption = {
        value: string;
        label: string;
        disabled?: boolean;
    };
</script>

<script lang="ts">
    import * as Select from '@/components/ui/select';
    import { cn } from '@/lib/utils';

    let {
        id,
        name,
        value = $bindable(''),
        options,
        placeholder = 'Select…',
        required = false,
        disabled = false,
        onValueChange,
        class: className,
    }: {
        id?: string;
        /** When set, a hidden input carries the value so Inertia <Form> and native forms submit it. */
        name?: string;
        value?: string;
        options: SelectOption[];
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        onValueChange?: (value: string) => void;
        class?: string;
    } = $props();

    const selectedLabel = $derived(
        options.find((option) => option.value === value)?.label,
    );
</script>

<Select.Root
    type="single"
    {name}
    {required}
    {disabled}
    bind:value
    {onValueChange}
>
    <Select.Trigger {id} class={cn('w-full', className)}>
        <span data-slot="select-value">{selectedLabel ?? placeholder}</span>
    </Select.Trigger>
    <Select.Content>
        {#each options as option (option.value)}
            <Select.Item
                value={option.value}
                label={option.label}
                disabled={option.disabled}
            />
        {/each}
    </Select.Content>
</Select.Root>
