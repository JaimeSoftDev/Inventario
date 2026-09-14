<script setup>
import AppIcon from '@/components/AppIcon.vue'
import MemberChip from '@/components/MemberChip.vue'
import { ocultarToast, toastActual } from '@/composables/useToast'

function deshacer() {
  toastActual.value?.alDeshacer?.()
  ocultarToast()
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-6 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="translate-y-6 opacity-0"
    >
      <div
        v-if="toastActual"
        :key="toastActual.id"
        class="fixed inset-x-0 bottom-20 z-30 mx-auto w-max max-w-[92vw] px-4"
        role="status"
      >
        <div class="flex items-center gap-3 rounded-full bg-tinta py-2 pr-2 pl-2 text-fondo shadow-lg">
          <MemberChip v-if="toastActual.usuario" :usuario="toastActual.usuario" tamano="sm" />
          <span class="text-[14px] font-bold">{{ toastActual.texto }}</span>
          <button
            v-if="toastActual.alDeshacer"
            type="button"
            class="flex items-center gap-1.5 rounded-full px-3 py-2 text-[14px] font-bold text-acento-300"
            @click="deshacer"
          >
            <AppIcon name="deshacer" :size="16" />
            Deshacer
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
