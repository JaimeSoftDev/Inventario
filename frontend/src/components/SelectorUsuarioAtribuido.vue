<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUsuariosStore } from '@/stores/usuarios'

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
})

const emit = defineEmits(['update:modelValue'])

const auth = useAuthStore()
const usuariosStore = useUsuariosStore()

onMounted(async () => {
  if (usuariosStore.usuarios.length === 0) {
    await usuariosStore.cargar()
  }
  // Por defecto, el usuario actual (siempre puede atribuirse a sí mismo).
  if (!props.modelValue && auth.usuario) {
    emit('update:modelValue', auth.usuario.id)
  }
})
</script>

<template>
  <div class="form-grupo">
    <label for="usuario_atribuido_id">Registrar a nombre de</label>
    <select
      id="usuario_atribuido_id"
      :value="modelValue"
      required
      @change="emit('update:modelValue', Number($event.target.value))"
    >
      <option v-if="!usuariosStore.usuarios.length" disabled value="">Cargando usuarios…</option>
      <option v-for="usuario in usuariosStore.usuarios" :key="usuario.id" :value="usuario.id">
        {{ usuario.name }}{{ usuario.id === auth.usuario?.id ? ' (tú)' : '' }}
      </option>
    </select>
  </div>
</template>
