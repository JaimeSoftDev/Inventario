<script setup>
import { computed, onMounted, ref } from 'vue'
import MemberChip from '@/components/MemberChip.vue'
import { useAuthStore } from '@/stores/auth'
import { useUsuariosStore } from '@/stores/usuarios'

/**
 * Selector "a nombre de": la pieza que resuelve el requisito central de la
 * app, registrar un movimiento a nombre de cualquier miembro del hogar.
 *
 * Por defecto se preselecciona el usuario actual (siempre permitido). Si no
 * tiene permiso para atribuir a otros, el resto aparece deshabilitado con
 * el motivo, en vez de dejar que falle con un 403 al confirmar.
 */
const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  etiqueta: { type: String, default: 'A nombre de' },
})

const emit = defineEmits(['update:modelValue'])

const auth = useAuthStore()
const usuariosStore = useUsuariosStore()

const VISIBLES = 4
const mostrarTodos = ref(false)

onMounted(async () => {
  if (usuariosStore.usuarios.length === 0) {
    await usuariosStore.cargar()
  }
  if (!props.modelValue && auth.usuario) {
    emit('update:modelValue', auth.usuario.id)
  }
})

const puedeAtribuirAOtros = computed(() => auth.usuario?.puede_atribuir_a_otros === true)

const ordenados = computed(() => {
  // El usuario actual siempre primero: es el caso más frecuente.
  const yo = usuariosStore.usuarios.filter((usuario) => usuario.id === auth.usuario?.id)
  const resto = usuariosStore.usuarios.filter((usuario) => usuario.id !== auth.usuario?.id)
  return [...yo, ...resto]
})

const mostrados = computed(() =>
  mostrarTodos.value ? ordenados.value : ordenados.value.slice(0, VISIBLES),
)
const ocultos = computed(() => Math.max(0, ordenados.value.length - VISIBLES))

function seleccionar(usuario) {
  if (usuario.id !== auth.usuario?.id && !puedeAtribuirAOtros.value) return
  emit('update:modelValue', usuario.id)
}
</script>

<template>
  <section>
    <h3 class="etiqueta-seccion">{{ etiqueta }}</h3>

    <div class="mt-2.5 flex flex-wrap items-start gap-3">
      <button
        v-for="usuario in mostrados"
        :key="usuario.id"
        type="button"
        class="flex w-14 flex-col items-center gap-1.5"
        :disabled="usuario.id !== auth.usuario?.id && !puedeAtribuirAOtros"
        :class="
          usuario.id !== auth.usuario?.id && !puedeAtribuirAOtros ? 'opacity-35' : ''
        "
        @click="seleccionar(usuario)"
      >
        <MemberChip :usuario="usuario" tamano="lg" :seleccionado="modelValue === usuario.id" />
        <span
          class="max-w-full truncate text-[12px]"
          :class="modelValue === usuario.id ? 'font-bold' : 'text-arena-500'"
        >
          {{ usuario.id === auth.usuario?.id ? usuario.name : usuario.name.split(' ')[0] }}
        </span>
      </button>

      <button
        v-if="ocultos > 0 && !mostrarTodos"
        type="button"
        class="flex w-14 flex-col items-center gap-1.5"
        @click="mostrarTodos = true"
      >
        <span
          class="flex h-14 w-14 items-center justify-center rounded-full bg-arena-200 font-display text-[16px] text-arena-600"
        >
          +{{ ocultos }}
        </span>
        <span class="text-[12px] text-arena-500">más</span>
      </button>
    </div>

    <p v-if="!puedeAtribuirAOtros" class="mt-2 text-[13px] text-arena-500">
      Solo puedes registrar movimientos a tu nombre.
    </p>
  </section>
</template>
