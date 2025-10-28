import { createApp } from 'vue'
// import './style.css'
import App from './App.vue'
import './assets/main.css'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import router from './router'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import './bootstrap'
const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(Toast, {
  position: 'top-right',
  timeout: 3000,
  closeOnClick: true,
  pauseOnHover: true,
})
app.use(router)
app.use(VueApexCharts)

app.mount('#app')