<script lang="ts">
    type Datum = { label: string; value: number };

    let { data }: { data: Datum[] } = $props();

    let hovered = $state<number | null>(null);

    const width = 480;
    const height = 140;
    const padX = 8;
    const padY = 16;

    const max = $derived(Math.max(1, ...data.map((d) => d.value)));

    const points = $derived(
        data.map((d, i) => ({
            x: padX + (i * (width - padX * 2)) / Math.max(1, data.length - 1),
            y: height - padY - (d.value / max) * (height - padY * 2),
            ...d,
        })),
    );

    const linePath = $derived(
        points.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' '),
    );

    const areaPath = $derived(
        points.length > 0
            ? `${linePath} L${points[points.length - 1].x},${height - padY} L${points[0].x},${height - padY} Z`
            : '',
    );

    function onMove(event: PointerEvent & { currentTarget: SVGSVGElement }) {
        const rect = event.currentTarget.getBoundingClientRect();
        const relX = ((event.clientX - rect.left) / rect.width) * width;
        let closest = 0;
        let closestDist = Infinity;
        points.forEach((p, i) => {
            const dist = Math.abs(p.x - relX);
            if (dist < closestDist) {
                closestDist = dist;
                closest = i;
            }
        });
        hovered = closest;
    }
</script>

{#if data.length === 0}
    <p class="py-8 text-center text-sm text-muted-foreground">
        No data yet.
    </p>
{:else}
    <div class="relative">
        <svg
            viewBox="0 0 {width} {height}"
            class="h-[160px] w-full"
            role="img"
            aria-label="New products added over the last 6 months"
            onpointermove={onMove}
            onpointerleave={() => (hovered = null)}
        >
            <line
                x1={padX}
                y1={height - padY}
                x2={width - padX}
                y2={height - padY}
                class="stroke-border"
                stroke-width="1"
            />
            <line
                x1={padX}
                y1={padY / 2}
                x2={width - padX}
                y2={padY / 2}
                class="stroke-border"
                stroke-width="1"
                stroke-dasharray="3 4"
            />
            <path d={areaPath} class="fill-primary/10" />
            <path
                d={linePath}
                fill="none"
                class="stroke-primary"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            {#if hovered !== null}
                {@const p = points[hovered]}
                <line
                    x1={p.x}
                    y1={padY / 2}
                    x2={p.x}
                    y2={height - padY}
                    class="stroke-muted-foreground/40"
                    stroke-width="1"
                />
            {/if}
            {#each points as p, i (p.label)}
                <circle
                    cx={p.x}
                    cy={p.y}
                    r={hovered === i ? 4.5 : 3}
                    class="fill-background stroke-primary transition-[r]"
                    stroke-width="2"
                />
            {/each}
            {#each points as p, i (p.label + '-label')}
                <text
                    x={p.x}
                    y={height - 2}
                    text-anchor="middle"
                    class="fill-muted-foreground text-[10px]"
                >
                    {p.label}
                </text>
            {/each}
        </svg>
        {#if hovered !== null}
            {@const p = points[hovered]}
            <div
                class="pointer-events-none absolute -translate-x-1/2 -translate-y-full rounded-md border bg-popover px-2 py-1 text-xs whitespace-nowrap text-popover-foreground shadow-sm"
                style="left: {(p.x / width) * 100}%; top: {(p.y / height) * 100}%"
            >
                <span class="font-medium">{p.value}</span> products · {p.label}
            </div>
        {/if}
    </div>
{/if}
