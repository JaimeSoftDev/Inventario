<script setup>
import { computed } from 'vue'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import EstadoChip from '@/components/EstadoChip.vue'
import MemberChip from '@/components/MemberChip.vue'
import { formateaCantidad } from '@/composables/useEstadoProducto'

/**
 * Fila del histórico de movimientos.
 *
 * Lo importante del dominio: se distingue A QUIÉN se atribuye (avatar
 * sólido, siempre presente) de QUIÉN lo registró (avatar discontinuo, solo
 * cuando difiere). Así la atribución libre no borra la trazabilidad.
 */
const props = defineProps({
  movimiento: { type: Object, required: true },
  estado: { type: String, default: 'sincronizado' }, // sincronizado | pendiente | error
  mensajeError: { type: String, default: null },
})

const emit = defineEmits(['revisar', 'descartar'])

const PRESENTACION = {
  compra: { icono: 'carrito', signo: '+', fondo: 'bg-oliva-100', color: 'text-oliva-700' },
  consumo: { icono: 'flechaAbajo', signo: '−', fondo: 'bg-acento-100', color: 'text-acento-700' },
  correccion: { icono: 'lapiz', signo: '=', fondo: 'bg-arena-200', color: 'text-arena-700' },
  transferencia: { icono: 'ubicacion', signo: '→', fondo: 'bg-arena-200', color: 'text-arena-700' },
  apertura: { icono: 'caja', signo: '·', fondo: 'bg-arena-200', color: 'text-arena-700' },
}

const vista = computed(() => PRESENTACION[props.movimiento.tipo] ?? PRESENTACION.correccion)
const esError = computed(() => props.estado === 'error')
const esPendiente = computed(() => props.estado === 'pendiente')

const atribuido = computed(() => props.movimiento.atribuido_a ?? null)
const registrador = computed(() => props.movimiento.registrado_por ?? null)

// El registrador solo se muestra cuando aporta información nueva.
const muestraRegistrador = computed(
  () => registrador.value?.id && atribuido.value?.id && registrador.value.id !== atribuido.value.id,
)

const hora = computed(() => {
  if (!props.movimiento.created_at) return null
  return new Date(props.movimiento.created_at).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
  })
})

const cantidadLegible = computed(
  () => `${vista.value.signo}${formateaCantidad(Math.abs(Number(props.movimiento.cantidad ?? 0)))}`,
)
</script>

<template>
  <article
    class="rounded-md p-3.5"
    :class="[
      esError ? 'bg-acento-700 text-fondo' : 'bg-tarjeta',
      esPendiente ? 'border-[1.5px] border-dashed border-arena-300 bg-transparent' : '',
      !esError && !esPendiente ? 'shadow-sm' : '',
    ]"
  >
    <div class="flex items-start gap-3">
      <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
        :class="esError ? 'bg-acento-800 text-acento-50' : [vista.fondo, vista.color]"
      >
        <AppIcon :name="esError ? 'aviso' : vista.icono" :size="20" />
      </span>

      <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
          <p class="text-[16px] font-bold">
            <span class="font-display">{{ cantidadLegible }}</span>
            {{ movimiento.producto?.nombre }}
          </p>
          <span
            class="shrink-0 text-[13px] tabular-nums"
            :class="esError ? 'text-acento-100' : 'text-arena-500'"
          >
            {{ hora }}
          </span>
        </div>

        <p v-if="esError && mensajeError" class="mt-1 text-[14px] text-acento-100">
          {{ mensajeError }}
        </p>
        <p v-else-if="movimiento.nota" class="mt-1 text-[14px] text-arena-500">
          {{ movimiento.nota }}
        </p>

        <div v-if="!esError" class="mt-2 flex flex-wrap items-center gap-2">
          <span
            v-if="atribuido"
            class="inline-flex items-center gap-1.5 rounded-full bg-arena-100 py-1 pr-2.5 pl-1"
          >
            <MemberChip :usuario="{ id: atribuido.id, name: atribuido.nombre }" tamano="sm" />
            <span class="text-[13px] font-bold">{{ atribuido.nombre }}</span>
          </span>

          <span
            v-if="muestraRegistrador"
            class="inline-flex items-center gap-1.5 rounded-full border-[1.5px] border-dashed border-arena-300 py-1 pr-2.5 pl-1"
          >
            <MemberChip
              :usuario="{ id: registrador.id, name: registrador.nombre }"
              tamano="sm"
              variante="discontinuo"
            />
            <span class="text-[13px] text-arena-600">reg. {{ registrador.nombre }}</span>
          </span>

          <EstadoChip
            v-if="esPendiente"
            tono="pendiente"
            icono="sincronizar"
            texto="Pendiente"
            tamano="sm"
          />
        </div>

        <div v-if="esError" class="mt-3 flex flex-wrap gap-2">
          <AppButton variante="claro" tamano="sm" fuente="sans" @click="emit('revisar')">
            Revisar
          </AppButton>
          <AppButton variante="contorno-claro" tamano="sm" fuente="sans" @click="emit('descartar')">
            Descartar
          </AppButton>
        </div>
      </div>
    </div>
  </article>
</template>
