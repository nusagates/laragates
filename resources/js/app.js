import './bootstrap';
import { createApp } from "vue/dist/vue.esm-bundler";


const vueApp = createApp()

const components = import.meta.glob('../components/*.vue', {eager: true})

Object.entries(components).forEach(([path, definition]) => {
    // Get name of component, based on filename
    // "./components/Fruits.vue" will become "Fruits"
    const componentName = path.split('/').pop().replace(/\.\w+$/, '')

    // Register component on this Vue instance
    vueApp.component(componentName, definition.default)
})
// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as VuetifyComponents from 'vuetify/components'
import * as VuetifyDirectives from 'vuetify/directives'

const vuetify = createVuetify({
    VuetifyComponents,
    VuetifyDirectives,
    ssr:true
})
vueApp.use(vuetify).mount("#app")
