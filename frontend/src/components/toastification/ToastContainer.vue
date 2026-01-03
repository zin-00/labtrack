<template>
  <div v-if="toasts && toasts.length > 0" class="fixed top-4 right-4 z-50 flex flex-col items-end">
    <Toast
      v-for="toast in toasts"
      :key="toast.id"
      :toast="toast"
      @close="removeToast"
    />
  </div>
</template>

<script setup>
import Toast from './Toast.vue'

const props = defineProps({
  toasts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['remove'])

const removeToast = (id) => {
  emit('remove', id)
}
</script>
