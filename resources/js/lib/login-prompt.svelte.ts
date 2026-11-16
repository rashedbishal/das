/**
 * The storefront's shared login modal (mounted once in StorefrontLayout). Guests
 * who click a product get it in place of the sign-in page, and land on that
 * product once they have signed in.
 */
export type LoginPromptProduct = { name: string; image: string | null };

export const loginPrompt = $state<{
    open: boolean;
    /** Path to go to after signing in, e.g. "/products/dental-chair". */
    intended: string | null;
    product: LoginPromptProduct | null;
}>({
    open: false,
    intended: null,
    product: null,
});

export function promptLogin(
    intended: string,
    product: LoginPromptProduct | null = null,
) {
    loginPrompt.intended = intended;
    loginPrompt.product = product;
    loginPrompt.open = true;
}
