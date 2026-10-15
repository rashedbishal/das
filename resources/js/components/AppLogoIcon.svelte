<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="currentColor"
    class={className}
    {...rest}
>
    <text
        x="13.5"
        y="19"
        text-anchor="middle"
        font-family="Manrope, ui-sans-serif, system-ui, sans-serif"
        font-size="19"
        font-weight="800">D</text
    >
    <path d="M3.4 2h1.8v2.6h2.6v1.8H5.2V9H3.4V6.4H.8V4.6h2.6z" />
</svg>
