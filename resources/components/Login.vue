<template>
    <v-container fluid>
        <v-card class="mx-auto px-6 mt-16" max-width="344">
            <v-sheet class="d-flex justify-center my-2">
                <img width="100" src="/assets/images/logo.png"/>
            </v-sheet>
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
                    prepend-inner-icon="mdi-email"
                    density="comfortable"
                ></v-text-field>

                <v-text-field
                    clearable
                    label="Password"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock"
                    density="comfortable"
                    :type="field.user.showPassword?'text':'password'"
                    :append-inner-icon="field.user.showPassword?'mdi-eye-off':'mdi-eye'"
                    @click:appendInner="field.user.showPassword=!field.user.showPassword"
                ></v-text-field>

                <v-btn
                    block
                    color="success"
                    size="large"
                    variant="elevated"
                    density="comfortable"
                >Login</v-btn>
                <v-btn class="my-2"
                    block
                    color="info"
                    size="large"
                    variant="elevated"
                    density="comfortable"
                >Register</v-btn>
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
            field: {
                user:{
                    email: null,
                    password: null,
                    showPassword:false
                }
            },
            dialog: {},
            loading: {},
            selected: {},
            option: {},
            table: {},
        }
    },
    methods: {},
}
</script>

<style scoped>

</style>
