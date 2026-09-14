<script setup>
import { onMounted } from 'vue'
import { useProductosStore } from '@/stores/productos'

const productosStore = useProductosStore()

onMounted(() => {
  productosStore.cargar()
})

function stockBajo(producto) {
  return producto.stock_actual <= producto.stock_minimo
}
</script>

<template>
  <div>
    <div class="cabecera">
      <h1>Productos</h1>
      <RouterLink to="/productos/nuevo">
        <button type="button">+ Nuevo producto</button>
      </RouterLink>
    </div>

    <p v-if="productosStore.error" class="mensaje-error">{{ productosStore.error }}</p>
    <p v-if="productosStore.cargando && !productosStore.productos.length" class="mensaje-info">
      Cargando…
    </p>

    <ul class="lista-productos">
      <li v-for="producto in productosStore.productos" :key="producto.id" class="card">
        <RouterLink :to="{ name: 'productos.stock', params: { id: producto.id } }">
          <strong>{{ producto.nombre }}</strong>
        </RouterLink>
        <div class="lista-productos__detalle">
          <span :class="{ 'mensaje-error': stockBajo(producto) }">
            Stock: {{ producto.stock_actual }} {{ producto.unidad_medida?.abreviatura }}
          </span>
          <span v-if="producto.categoria" class="mensaje-info">{{ producto.categoria.nombre }}</span>
        </div>
      </li>
    </ul>

    <p v-if="!productosStore.cargando && !productosStore.productos.length" class="mensaje-info">
      No hay productos todavía.
    </p>
  </div>
</template>

<style scoped>
.cabecera {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.lista-productos {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.lista-productos__detalle {
  display: flex;
  justify-content: space-between;
  margin-top: 0.25rem;
  font-size: 0.9rem;
}
</style>
