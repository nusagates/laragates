import { router } from '@inertiajs/vue3';

export default {
    mounted(el, binding) {
        el.addEventListener('click', () => {
            router.visit(binding.value); // Use the router instance directly
        });
    },
    unmounted(el) {
        el.removeEventListener('click', () => {
            router.visit(binding.value);
        });
    },
};