<script lang="ts">
    /**
     * `shine` adds the storefront header's looping logo glint (motion.css,
     * "Logo shine"). Without it the logo is the plain image, as in the footer
     * and on the auth screens.
     */
    let { shine = false }: { shine?: boolean } = $props();

    let wrapper = $state<HTMLElement>();
    // Bumped on hover: remounting the glint restarts its loop right away.
    let run = $state(0);

    $effect(() => {
        const el = wrapper;

        if (!el) {
            return;
        }

        const restart = () => (run += 1);

        el.addEventListener('pointerenter', restart);

        return () => el.removeEventListener('pointerenter', restart);
    });

    // The mask is set inline so `/logo.png` resolves against the page. In a
    // stylesheet it would resolve against the Vite dev server, where it 404s
    // and the failed mask hides the glint entirely.
    const mask =
        '-webkit-mask-image: url(/logo.png); mask-image: url(/logo.png)';
</script>

{#if shine}
    <span bind:this={wrapper} class="sf-logo">
        <img
            src="/logo.png"
            alt="DAS logo"
            class="h-10 w-auto max-w-[220px] object-contain sm:h-12"
        />
        {#key run}
            <span
                class="sf-logo-glint h-10 sm:h-12"
                class:is-replay={run > 0}
                style={mask}
                aria-hidden="true"
            ></span>
        {/key}
    </span>
{:else}
    <img
        src="/logo.png"
        alt="DAS logo"
        class="h-10 w-auto max-w-[220px] object-contain sm:h-12"
    />
{/if}
