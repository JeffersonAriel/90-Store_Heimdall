import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createHead } from '@vueuse/head'
import router from './router'
import App from './App.vue'
import axios from 'axios'
import './assets/css/main.css'

// Detecta dinamicamente a subpasta do cPanel (ex: /~jeff2892)
const basePath = window.location.pathname.replace(/\/$/, '')

axios.interceptors.request.use((config) => {
  if (config.url && config.url.startsWith('/api/')) {
    config.url = `${basePath}${config.url}`
  }
  return config
})

const app = createApp(App)
const pinia = createPinia()
const head = createHead()

app.use(pinia)
app.use(router)
app.use(head)

app.mount('#app')
