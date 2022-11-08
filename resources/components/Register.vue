<template>
    <v-container fluid>
        <v-card class="mx-auto px-6 mt-16" max-width="344">
            <v-sheet class="d-flex justify-center my-2">
                <img width="100" src="/assets/images/logo.png"/>
            </v-sheet>
            <v-form>
                <v-text-field
                    class="mb-2"
                    clearable
                    label="Email"
                    variant="outlined"
                    prepend-inner-icon="mdi-email"
                    density="comfortable"
                    v-model="field.user.email"
                ></v-text-field>
                <v-text-field
                    class="mb-2"
                    clearable
                    label="Name"
                    variant="outlined"
                    prepend-inner-icon="mdi-account"
                    density="comfortable"
                    v-model="field.user.name"
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
                    v-model="field.user.password"
                ></v-text-field>
                <v-text-field
                    clearable
                    label="Password Confirmation"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock"
                    density="comfortable"
                    :type="field.user.showPasswordConfirmation?'text':'password'"
                    :append-inner-icon="field.user.showPasswordConfirmation?'mdi-eye-off':'mdi-eye'"
                    @click:appendInner="field.user.showPasswordConfirmation=!field.user.showPasswordConfirmation"
                    v-model="field.user.password_confirmation"
                ></v-text-field>

                <v-btn
                    @click="register"
                    :disabled="loading.register"
                    :loading="loading.register"
                    block
                    color="success"
                    size="large"
                    variant="elevated"
                    density="comfortable"
                >Submit
                </v-btn>
                <v-btn class="my-2"
                       block
                       color="info"
                       size="large"
                       variant="text"
                       density="comfortable"
                       href="/login"
                >Login
                </v-btn>
            </v-form>
        </v-card>
        <toast ref="message"/>
    </v-container>
</template>

<script>
import Toast from "./helpers/Toast.vue";
export default {
    name: "Register",
    components: {Toast},
    data() {
        return {
            collection: {},
            field: {
                user: {
                    email: null,
                    name: null,
                    password: null,
                    password_confirmation: null,
                    showPassword: false,
                    showPasswordConfirmation: false
                }
            },
            loading: {
                register: false
            },
        }
    },
    methods: {
        register() {
            this.loading.register = true
            axios.post('/api/v1/register', this.field.user).then(res => {
                if(res.data.code===200){
                    this.$refs.message.show(res.data.message)
                }else{
                    this.$refs.message.show(res.data.message, 'warning')
                }
            }).finally(() => {
                this.loading.register = false
            })
        }
    },
}
</script>

<style scoped>

</style>
