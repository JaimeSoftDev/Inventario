<script setup>
import { onMounted, reactive, ref } from 'vue'
import SelectorUsuarioAtribuido from '@/components/SelectorUsuarioAtribuido.vue'
import { useMovimientoStock } from '@/composables/useMovimientoStock'
import { useCatalogosStore } from '@/stores/catalogos'
import { useProductosStore } from '@/stores/productos'

const catalogos = useCatalogosStore()
const productosStore = useProductosStore()
const { registrarCompra } = useMovimientoStock()

const form = reactive({
  producto_id: '',
  ubicacion_id: '',
  cantidad: 1,
  fecha_caducidad: '',
  precio_unitario: '',
  usuario_atribuido_id: null,
  nota: '',
})

const enviando = ref(false)
const error = ref(null)
const mensajeExito = ref(null)

onMounted(async () => {
  await Promise.all([
    productosStore.productos.length ? Promise.resolve() : productosStore.cargar(),
    catalogos.cargarUbicaciones(),
  ])
})

async function enviar() {
  enviando.value = true
  error.value = null
  mensajeExito.value = null

  try {
    const { encolado } = await registrarCompra({
      ...form,
      fecha_caducidad: form.fecha_caducidad || null,
      precio_unitario: form.precio_unitario || null,
      nota: form.nota || null,
    })

    mensajeExito.value = encolado
      ? 'Sin conexión: la compra se guardó y se sincronizará automáticamente.'
      : 'Compra registrada.'

    form.cantidad = 1
    form.fecha_caducidad = ''
    form.precio_unitario = ''
    form.nota = ''
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo registrar la compra.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div>
    <h1>Registrar compra</h1>
    <form class="card" @submit.prevent="enviar">
      <div class="form-grupo">
        <label for="producto_id">Producto</label>
        <select id="producto_id" v-model="form.producto_id" required>
          <option value="" disabled>Selecciona…</option>
          <option v-for="p in productosStore.productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
        </select>
      </div>

      <div class="form-grupo">
        <label for="ubicacion_id">Ubicación</label>
        <select id="ubicacion_id" v-model="form.ubicacion_id" required>
          <option value="" disabled>Selecciona…</option>
          <option v-for="u in catalogos.ubicaciones" :key="u.id" :value="u.id">{{ u.nombre }}</option>
        </select>
      </div>

      <div class="form-grupo">
        <label for="cantidad">Cantidad</label>
        <input id="cantidad" v-model.number="form.cantidad" type="number" min="0.001" step="0.001" required />
      </div>

      <div class="form-grupo">
        <label for="fecha_caducidad">Fecha de caducidad (opcional)</label>
        <input id="fecha_caducidad" v-model="form.fecha_caducidad" type="date" />
      </div>

      <div class="form-grupo">
        <label for="precio_unitario">Precio unitario (opcional)</label>
        <input id="precio_unitario" v-model.number="form.precio_unitario" type="number" min="0" step="0.01" />
      </div>

      <SelectorUsuarioAtribuido v-model="form.usuario_atribuido_id" />

      <div class="form-grupo">
        <label for="nota">Nota (opcional)</label>
        <input id="nota" v-model="form.nota" />
      </div>

      <p v-if="error" class="mensaje-error">{{ error }}</p>
      <p v-if="mensajeExito" class="mensaje-info">{{ mensajeExito }}</p>

      <div class="form-acciones">
        <button type="submit" :disabled="enviando">{{ enviando ? 'Guardando…' : 'Registrar compra' }}</button>
      </div>
    </form>
  </div>
</template>
