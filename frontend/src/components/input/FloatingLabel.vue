<!-- FloatingInput.vue -->
<template>
  <div class="relative">
    <input
      :id="id"
      :type="type"
      v-model="modelValue"
      :required="required"
      :autofocus="autofocus"
      :placeholder="placeholder"
      class="peer w-full px-4 pt-5 pb-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
      :class="{ 'border-red-500': error }"
    />
    <label
      :for="id"
      class="absolute left-4 top-2 text-gray-500 text-sm transition-all duration-200 
            peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base
            peer-focus:top-2 peer-focus:text-sm peer-focus:text-blue-600"
    >
      {{ label }}
    </label>
    <p v-if="error" class="text-red-600 text-sm mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  id: String,
  label: String,
  type: { type: String, default: "text" },
  placeholder: { type: String, default: " " }, // must have a placeholder for peer to work
  modelValue: String,
  required: Boolean,
  autofocus: Boolean,
  error: String
})

defineEmits(["update:modelValue"])
</script>
