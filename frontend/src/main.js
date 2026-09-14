import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'
import { iniciarSincronizador } from '@/composables/useSincronizador'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

// Arranca el listener que reintenta la cola de movimientos offline en
// cuanto el dispositivo recupera la conexión.
iniciarSincronizador()
