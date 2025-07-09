import { router } from '@inertiajs/vue3';

export default {
    mounted(el, binding) {
        // Add pointer cursor style
        el.style.cursor = 'pointer';

        // Enable Vuetify's hover effect by setting the link prop
        if (el.__vue__) {
            el.__vue__.link = true;
        } else {
            // For Vuetify 3, set attribute directly
            el.setAttribute('link', '');
        }

        // Store the click handler function for proper removal later
        const clickHandler = () => {
            router.visit(binding.value);
        };

        // Save reference to the handler
        el._navToHandler = clickHandler;

        // Add click event listener
        el.addEventListener('click', clickHandler);
    },

    unmounted(el) {
        // Remove the event listener using the stored reference
        if (el._navToHandler) {
            el.removeEventListener('click', el._navToHandler);
            delete el._navToHandler;
        }

        // Reset cursor style
        el.style.cursor = '';
    }
};