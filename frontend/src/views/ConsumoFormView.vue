<script setup>
import { onMounted, reactive, ref } from 'vue'
import SelectorUsuarioAtribuido from '@/components/SelectorUsuarioAtribuido.vue'
import { useMovimientoStock } from '@/composables/useMovimientoStock'
import { useProductosStore } from '@/stores/productos'

const productosStore = useProductosStore()
const { registrarConsumo } = useMovimientoStock()

const form = reactive({
  producto_id: '',
  cantidad: 1,
  usuario_atribuido_id: null,
  nota: '',
})

const enviando = ref(false)
const error = ref(null)
const mensajeExito = ref(null)

onMounted(() => {
  if (!productosStore.productos.length) {
    productosStore.cargar()
  }
})

async function enviar() {
  enviando.value = true
  error.value = null
  mensajeExito.value = null

  try {
    const { encolado } = await registrarConsumo({
      ...form,
      nota: form.nota || null,
    })

    mensajeExito.value = encolado
      ? 'Sin conexión: el consumo se guardó y se sincronizará automáticamente.'
      : 'Consumo registrado.'

    form.cantidad = 1
    form.nota = ''
  } catch (e) {
    // 422 (stock insuficiente) o 403 (atribución no autorizada): no se
    // encola, se informa directamente al usuario.
    error.value = e.response?.data?.message ?? 'No se pudo registrar el consumo.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div>
    <h1>Registrar consumo</h1>
    <form class="card" @submit.prevent="enviar">
      <div class="form-grupo">
        <label for="producto_id">Producto</label>
        <select id="producto_id" v-model="form.producto_id" required>
          <option value="" disabled>Selecciona…</option>
          <option v-for="p in productosStore.productos" :key="p.id" :value="p.id">
            {{ p.nombre }} (stock: {{ p.stock_actual }})
          </option>
        </select>
      </div>

      <div class="form-grupo">
        <label for="cantidad">Cantidad</label>
        <input id="cantidad" v-model.number="form.cantidad" type="number" min="0.001" step="0.001" required />
      </div>

      <SelectorUsuarioAtribuido v-model="form.usuario_atribuido_id" />

      <div class="form-grupo">
        <label for="nota">Nota (opcional)</label>
        <input id="nota" v-model="form.nota" />
      </div>

      <p v-if="error" class="mensaje-error">{{ error }}</p>
      <p v-if="mensajeExito" class="mensaje-info">{{ mensajeExito }}</p>

      <div class="form-acciones">
        <button type="submit" :disabled="enviando">{{ enviando ? 'Guardando…' : 'Registrar consumo' }}</button>
      </div>
    </form>
  </div>
</template>
