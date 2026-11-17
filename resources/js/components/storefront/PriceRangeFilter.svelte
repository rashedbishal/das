<script lang="ts">
    import type { PriceRange } from '@/data/catalogue';

    /**
     * Two-thumb price slider with matching min/max inputs. Changes apply on their
     * own after a short pause (no submit button): quickly while sliding, a bit
     * later while typing, and at once on blur or Enter.
     */
    let {
        id,
        range,
        min,
        max,
        currency = 'BDT',
        onchange,
    }: {
        id: string;
        /** Lowest/highest shown price for the current selection; null if none. */
        range: PriceRange | null;
        min?: number | undefined;
        max?: number | undefined;
        currency?: string;
        onchange: (min: number | undefined, max: number | undefined) => void;
    } = $props();

    const SLIDE_DELAY_MS = 300;
    const TYPE_DELAY_MS = 650;

    const floor = $derived(range ? Math.floor(range.min) : 0);
    const ceil = $derived(range ? Math.ceil(range.max) : 0);
    const step = $derived(niceStep(ceil - floor));

    // Same currency wording as the product cards (formatPrice), e.g. "BDT".
    const currencyLabel = $derived(
        new Intl.NumberFormat('en-US', { style: 'currency', currency })
            .formatToParts(0)
            .find((part) => part.type === 'currency')?.value ?? currency,
    );

    let lo = $state(0);
    let hi = $state(0);
    let loText = $state('');
    let hiText = $state('');
    let timer: ReturnType<typeof setTimeout> | undefined;
    /** True while an edit is waiting to be applied, so props don't overwrite it. */
    let pending = false;

    // Follow the applied filter (chips, "Clear all", back/forward) and new bounds.
    $effect(() => {
        const nextLo = min ?? floor;
        const nextHi = max ?? ceil;

        if (pending) {
            return;
        }

        lo = nextLo;
        hi = nextHi;
        loText = format(nextLo);
        hiText = format(nextHi);
    });

    $effect(() => () => clearTimeout(timer));

    function niceStep(span: number) {
        if (span <= 0) {
            return 1;
        }

        return Math.max(1, 10 ** Math.floor(Math.log10(span / 100)));
    }

    function clamp(value: number) {
        return Math.min(ceil, Math.max(floor, value));
    }

    function format(value: number) {
        return Math.round(value).toLocaleString('en-US');
    }

    function parse(text: string): number | null {
        const digits = text.replace(/[^\d.]/g, '');

        return digits === '' || Number.isNaN(Number(digits))
            ? null
            : Number(digits);
    }

    function percent(value: number) {
        return ceil > floor ? ((clamp(value) - floor) / (ceil - floor)) * 100 : 0;
    }

    function schedule(delay: number) {
        pending = true;
        clearTimeout(timer);
        timer = setTimeout(commit, delay);
    }

    function commit() {
        clearTimeout(timer);
        pending = false;

        let nextLo = clamp(lo);
        let nextHi = clamp(hi);

        if (nextLo > nextHi) {
            [nextLo, nextHi] = [nextHi, nextLo];
        }

        lo = nextLo;
        hi = nextHi;
        loText = format(nextLo);
        hiText = format(nextHi);

        // The full range means "any price", so it isn't a filter.
        const outMin = nextLo > floor ? nextLo : undefined;
        const outMax = nextHi < ceil ? nextHi : undefined;

        if (outMin !== min || outMax !== max) {
            onchange(outMin, outMax);
        }
    }

    // Thumb positions, kept in order even while a typed min is above the max.
    const loThumb = $derived(Math.min(clamp(lo), clamp(hi)));
    const hiThumb = $derived(Math.max(clamp(lo), clamp(hi)));

    // The handles stop at each other. The native input is snapped back too:
    // once they meet, the state no longer changes, so Svelte wouldn't reset it.
    function slideLo(input: HTMLInputElement) {
        lo = Math.min(Number(input.value), hiThumb);
        input.value = String(lo);
        loText = format(lo);
        schedule(SLIDE_DELAY_MS);
    }

    function slideHi(input: HTMLInputElement) {
        hi = Math.max(Number(input.value), loThumb);
        input.value = String(hi);
        hiText = format(hi);
        schedule(SLIDE_DELAY_MS);
    }

    function type(which: 'lo' | 'hi', text: string) {
        const value = parse(text);

        if (which === 'lo') {
            loText = text;
        } else {
            hiText = text;
        }

        if (value === null) {
            return;
        }

        if (which === 'lo') {
            lo = value;
        } else {
            hi = value;
        }

        schedule(TYPE_DELAY_MS);
    }

    function onBlur() {
        if (pending) {
            commit();
        } else {
            // Nothing valid was typed: show the applied values again.
            loText = format(lo);
            hiText = format(hi);
        }
    }

    function onKeydown(event: KeyboardEvent) {
        if (event.key === 'Enter') {
            event.preventDefault();
            commit();
        }
    }
</script>

{#if range && ceil > floor}
    <div class="price-slider" style:--lo="{percent(loThumb)}%" style:--hi="{percent(hiThumb)}%">
        <div class="price-slider-track" aria-hidden="true">
            <div class="price-slider-fill"></div>
        </div>
        <input
            type="range"
            min={floor}
            max={ceil}
            {step}
            value={loThumb}
            aria-label="Minimum price"
            aria-valuetext="{currencyLabel} {format(loThumb)}"
            class:on-top={loThumb >= ceil - step}
            oninput={(event) => slideLo(event.currentTarget)}
        />
        <input
            type="range"
            min={floor}
            max={ceil}
            {step}
            value={hiThumb}
            aria-label="Maximum price"
            aria-valuetext="{currencyLabel} {format(hiThumb)}"
            oninput={(event) => slideHi(event.currentTarget)}
        />
    </div>
    <div class="mt-4 grid grid-cols-[1fr_auto_1fr] items-end gap-2">
        <label class="min-w-0 text-[10px] text-muted-foreground">
            Min ({currencyLabel})
            <input
                id="{id}-min"
                class="field field-sm mt-1.5"
                inputmode="numeric"
                autocomplete="off"
                value={loText}
                oninput={(event) => type('lo', event.currentTarget.value)}
                onblur={onBlur}
                onkeydown={onKeydown}
            />
        </label>
        <span class="pb-2.5 text-[11px] text-muted-foreground" aria-hidden="true"
            >–</span
        >
        <label class="min-w-0 text-[10px] text-muted-foreground">
            Max ({currencyLabel})
            <input
                id="{id}-max"
                class="field field-sm mt-1.5"
                inputmode="numeric"
                autocomplete="off"
                value={hiText}
                oninput={(event) => type('hi', event.currentTarget.value)}
                onblur={onBlur}
                onkeydown={onKeydown}
            />
        </label>
    </div>
{:else if range}
    <p class="text-[11px] text-muted-foreground">
        All listed prices here are {currencyLabel}
        {format(range.min)}.
    </p>
{:else}
    <p class="text-[11px] text-muted-foreground">
        No products with listed prices in this selection.
    </p>
{/if}
