<template>
    <v-snackbar
        :color="color"
        v-model="visible"
        :timeout="1500"
    >
        <b v-text="message"/>

    </v-snackbar>
</template>

<script>
export default {
    name: "Toast",
    data() {
        return {
            visible: false,
            message: null,
            color: 'success',
            duration: 1500
        }
    },
    methods: {
        show(message, color = 's', duration) {
            this.message = message
            this.color = color == 's' ? 'green darken-4' : 'red darken-4'
            this.duration = duration==undefined?1500:duration;
            this.visible = true
        }
    }
}
</script>

<style scoped>

</style>
