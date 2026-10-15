<script lang="ts">
    import {
        DateFormatter,
        getLocalTimeZone,
        parseDate,
        type DateValue,
    } from '@internationalized/date';
    import CalendarIcon from '@lucide/svelte/icons/calendar';
    import XIcon from '@lucide/svelte/icons/x';
    import { Calendar } from '@/components/ui/calendar';
    import * as Popover from '@/components/ui/popover';
    import { cn } from '@/lib/utils';

    let {
        id,
        name,
        value = $bindable(''),
        placeholder = 'Pick a date',
        required = false,
        disabled = false,
        clearable = true,
        min,
        max,
        class: className,
    }: {
        id?: string;
        /** When set, the value is submitted as `YYYY-MM-DD` under this name. */
        name?: string;
        /** ISO date string, `YYYY-MM-DD`, or '' for none. */
        value?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        clearable?: boolean;
        min?: string;
        max?: string;
        class?: string;
    } = $props();

    let open = $state(false);
    let trigger = $state<HTMLButtonElement | null>(null);

    const formatter = new DateFormatter('en-GB', { dateStyle: 'medium' });

    const toDate = (iso?: string): DateValue | undefined => {
        if (!iso) return undefined;
        try {
            return parseDate(iso);
        } catch {
            return undefined;
        }
    };

    const selected = $derived(toDate(value));

    function select(date: DateValue | undefined) {
        value = date?.toString() ?? '';
        open = false;
    }
</script>

<div class={cn('relative w-full', className)}>
    <Popover.Root bind:open>
        <Popover.Trigger
            bind:ref={trigger}
            {id}
            {disabled}
            class="flex h-9 w-full items-center gap-2 rounded-md border border-input bg-transparent px-3 text-left text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 data-[state=open]:border-ring dark:bg-input/30 dark:hover:bg-input/50"
        >
            <CalendarIcon class="size-4 shrink-0 text-muted-foreground" />
            <span class={cn('truncate', !selected && 'text-muted-foreground')}>
                {selected
                    ? formatter.format(selected.toDate(getLocalTimeZone()))
                    : placeholder}
            </span>
        </Popover.Trigger>
        <Popover.Content class="w-auto p-0" align="start">
            <Calendar
                type="single"
                value={selected}
                onValueChange={select}
                minValue={toDate(min)}
                maxValue={toDate(max)}
                captionLayout="dropdown"
                initialFocus
            />
        </Popover.Content>
    </Popover.Root>

    {#if clearable && selected && !disabled}
        <button
            type="button"
            class="absolute top-1/2 right-2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
            aria-label="Clear date"
            onclick={() => (value = '')}
        >
            <XIcon class="size-3.5" />
        </button>
    {/if}

    {#if name}
        <!-- Visually hidden but focusable, so native `required` validation still works. -->
        <input
            class="sr-only"
            tabindex="-1"
            aria-hidden="true"
            {name}
            {value}
            {required}
            {disabled}
            onfocus={() => trigger?.focus()}
        />
    {/if}
</div>
