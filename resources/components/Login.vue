<template>
    <v-container fluid>
        <v-card class="mx-auto px-6 py-8" max-width="344">
            <v-form
                v-model="form"
                @submit.prevent="onSubmit"
            >
                <v-text-field
                    class="mb-2"
                    clearable
                    label="Email"
                    hint="Ketikkan email yang telah terdaftar"
                    variant="outlined"
                ></v-text-field>

                <v-text-field
                    clearable
                    label="Password"
                    variant="outlined"
                ></v-text-field>

                <v-btn
                    block
                    color="success"
                    size="large"
                    variant="elevated"
                >Login
                </v-btn>
            </v-form>
        </v-card>
    </v-container>
</template>

<script>
export default {
    name: "Login",
    data() {
        return {
            collection: {},
            field: {},
            dialog: {},
            loading: {},
            selected: {},
            option: {},
            table: {},
        }
    },
    methods: {},
    mounted() {
    },
    watch: {}
}
</script>

<style scoped>

</style>
