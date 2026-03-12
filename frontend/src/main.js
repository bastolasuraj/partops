import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { installGlobalErrorLogging } from './services/api'
import './assets/css/main.css'

installGlobalErrorLogging()

const app = createApp(App)
app.use(router)

// Wait for the initial route to resolve before mounting to avoid flicker/redirect loops
router.isReady().then(() => {
  app.mount('#app')
})
