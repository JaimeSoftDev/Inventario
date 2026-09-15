<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import { useCatalogosStore } from '@/stores/catalogos'
import { useProductosStore } from '@/stores/productos'

/**
 * Alta de producto: define la "ficha" (unidad, ubicación habitual, mínimo,
 * caducidad típica). El stock no se introduce aquí; llega con la primera
 * compra, que es la que crea el lote.
 */
const router = useRouter()
const catalogos = useCatalogosStore()
const productosStore = useProductosStore()

const form = reactive({
  nombre: '',
  descripcion: '',
  categoria_id: '',
  unidad_medida_id: '',
  ubicacion_por_defecto_id: '',
  stock_minimo: 0,
  dias_caducidad_por_defecto: '',
})

const enviando = ref(false)
const error = ref(null)

onMounted(async () => {
  await catalogos.cargarTodos()
  form.unidad_medida_id = catalogos.unidadesMedida[0]?.id ?? ''
})

async function enviar() {
  if (enviando.value) return

  enviando.value = true
  error.value = null

  try {
    const producto = await productosStore.crear({
      ...form,
      categoria_id: form.categoria_id || null,
      ubicacion_por_defecto_id: form.ubicacion_por_defecto_id || null,
      dias_caducidad_por_defecto: form.dias_caducidad_por_defecto || null,
    })
    router.replace({ name: 'producto', params: { id: producto.id } })
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo crear el producto.'
  } finally {
    enviando.value = false
  }
}

const CLASE_CAMPO =
  'mt-2 w-full rounded-md border border-arena-300 bg-arena-50 px-4 py-3.5 outline-none focus:border-acento-400'
</script>

<template>
  <div class="mx-auto w-full max-w-[460px] px-5 pt-4 pb-8">
    <header class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-[30px] leading-tight">Nuevo producto</h1>
        <p class="mt-1 text-[14px] text-arena-500">El stock entra después, con la primera compra.</p>
      </div>
      <button
        type="button"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-arena-300 text-arena-700"
        aria-label="Cerrar"
        @click="router.back()"
      >
        <AppIcon name="cerrar" :size="20" />
      </button>
    </header>

    <form class="mt-6" @submit.prevent="enviar">
      <label class="block">
        <span class="etiqueta-seccion">Nombre</span>
        <input v-model="form.nombre" required :class="CLASE_CAMPO" placeholder="Leche entera" />
      </label>

      <label class="mt-4 block">
        <span class="etiqueta-seccion">Descripción</span>
        <input v-model="form.descripcion" :class="CLASE_CAMPO" placeholder="brik 1 L" />
      </label>

      <section class="mt-5">
        <h2 class="etiqueta-seccion">Unidad de medida</h2>
        <div class="mt-2.5 flex flex-wrap gap-2">
          <button
            v-for="unidad in catalogos.unidadesMedida"
            :key="unidad.id"
            type="button"
            class="h-11 rounded-full px-4 text-[14px] font-bold transition"
            :class="
              form.unidad_medida_id === unidad.id
                ? 'bg-tinta text-fondo'
                : 'border border-arena-300 text-arena-700'
            "
            @click="form.unidad_medida_id = unidad.id"
          >
            {{ unidad.abreviatura }}
          </button>
        </div>
      </section>

      <section class="mt-5">
        <h2 class="etiqueta-seccion">Ubicación habitual</h2>
        <div class="mt-2.5 flex flex-wrap gap-2">
          <button
            v-for="ubicacion in catalogos.ubicaciones"
            :key="ubicacion.id"
            type="button"
            class="h-11 rounded-full px-4 text-[14px] font-bold transition"
            :class="
              form.ubicacion_por_defecto_id === ubicacion.id
                ? 'bg-oliva-500 text-fondo'
                : 'border border-arena-300 text-arena-700'
            "
            @click="form.ubicacion_por_defecto_id = ubicacion.id"
          >
            {{ ubicacion.nombre }}
          </button>
        </div>
      </section>

      <section class="mt-5">
        <h2 class="etiqueta-seccion">Categoría</h2>
        <div class="mt-2.5 flex flex-wrap gap-2">
          <button
            v-for="categoria in catalogos.categorias"
            :key="categoria.id"
            type="button"
            class="h-11 rounded-full px-4 text-[14px] font-bold transition"
            :class="
              form.categoria_id === categoria.id
                ? 'bg-tinta text-fondo'
                : 'border border-arena-300 text-arena-700'
            "
            @click="form.categoria_id = form.categoria_id === categoria.id ? '' : categoria.id"
          >
            {{ categoria.nombre }}
          </button>
        </div>
      </section>

      <div class="mt-5 grid grid-cols-2 gap-3">
        <label class="block">
          <span class="etiqueta-seccion">Stock mínimo</span>
          <input
            v-model.number="form.stock_minimo"
            type="number"
            inputmode="decimal"
            min="0"
            step="any"
            :class="CLASE_CAMPO"
          />
        </label>
        <label class="block">
          <span class="etiqueta-seccion">Caduca en (días)</span>
          <input
            v-model.number="form.dias_caducidad_por_defecto"
            type="number"
            inputmode="numeric"
            min="0"
            step="1"
            :class="CLASE_CAMPO"
            placeholder="—"
          />
        </label>
      </div>

      <p v-if="error" class="mt-4 rounded-md bg-acento-100 px-4 py-3 text-[14px] text-acento-800">
        {{ error }}
      </p>

      <AppButton
        tamano="lg"
        bloque
        class="mt-7"
        :deshabilitado="enviando || !form.nombre || !form.unidad_medida_id"
        @click="enviar"
      >
        {{ enviando ? 'Guardando…' : 'Guardar producto' }}
      </AppButton>
      <button type="submit" class="sr-only">Guardar</button>
    </form>
  </div>
</template>
