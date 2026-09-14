<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCatalogosStore } from '@/stores/catalogos'
import { useProductosStore } from '@/stores/productos'

const catalogos = useCatalogosStore()
const productosStore = useProductosStore()
const router = useRouter()

const form = reactive({
  nombre: '',
  descripcion: '',
  categoria_id: '',
  unidad_medida_id: '',
  ubicacion_por_defecto_id: '',
  stock_minimo: 0,
  dias_caducidad_por_defecto: '',
  precio_referencia: '',
  notas: '',
})

const enviando = ref(false)
const error = ref(null)

onMounted(() => {
  catalogos.cargarTodos()
})

async function enviar() {
  enviando.value = true
  error.value = null
  try {
    const producto = await productosStore.crear({
      ...form,
      categoria_id: form.categoria_id || null,
      ubicacion_por_defecto_id: form.ubicacion_por_defecto_id || null,
      dias_caducidad_por_defecto: form.dias_caducidad_por_defecto || null,
      precio_referencia: form.precio_referencia || null,
    })
    router.push({ name: 'productos.stock', params: { id: producto.id } })
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo crear el producto.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div>
    <h1>Nuevo producto</h1>
    <form class="card" @submit.prevent="enviar">
      <div class="form-grupo">
        <label for="nombre">Nombre</label>
        <input id="nombre" v-model="form.nombre" required />
      </div>

      <div class="form-grupo">
        <label for="unidad_medida_id">Unidad de medida</label>
        <select id="unidad_medida_id" v-model="form.unidad_medida_id" required>
          <option value="" disabled>Selecciona…</option>
          <option v-for="u in catalogos.unidadesMedida" :key="u.id" :value="u.id">
            {{ u.nombre }} ({{ u.abreviatura }})
          </option>
        </select>
      </div>

      <div class="form-grupo">
        <label for="categoria_id">Categoría</label>
        <select id="categoria_id" v-model="form.categoria_id">
          <option value="">Sin categoría</option>
          <option v-for="c in catalogos.categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>

      <div class="form-grupo">
        <label for="ubicacion_por_defecto_id">Ubicación por defecto</label>
        <select id="ubicacion_por_defecto_id" v-model="form.ubicacion_por_defecto_id">
          <option value="">Sin definir</option>
          <option v-for="u in catalogos.ubicaciones" :key="u.id" :value="u.id">{{ u.nombre }}</option>
        </select>
      </div>

      <div class="form-grupo">
        <label for="stock_minimo">Stock mínimo</label>
        <input id="stock_minimo" v-model.number="form.stock_minimo" type="number" min="0" step="0.001" />
      </div>

      <div class="form-grupo">
        <label for="dias_caducidad_por_defecto">Días de caducidad por defecto</label>
        <input
          id="dias_caducidad_por_defecto"
          v-model.number="form.dias_caducidad_por_defecto"
          type="number"
          min="0"
        />
      </div>

      <div class="form-grupo">
        <label for="notas">Notas</label>
        <textarea id="notas" v-model="form.notas" rows="2" />
      </div>

      <p v-if="error" class="mensaje-error">{{ error }}</p>

      <div class="form-acciones">
        <button type="submit" :disabled="enviando">{{ enviando ? 'Guardando…' : 'Guardar' }}</button>
      </div>
    </form>
  </div>
</template>
