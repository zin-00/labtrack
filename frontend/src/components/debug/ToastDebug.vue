<template>
  <div class="fixed bottom-4 left-4 z-50 bg-gray-800 text-white p-4 rounded-lg shadow-lg">
    <h3 class="font-bold mb-2">Toast Debug Panel</h3>
    <div class="space-y-2 text-sm">
      <p>Active Toasts: {{ toast.toasts.value.length }}</p>
      <div v-for="t in toast.toasts.value" :key="t.id" class="border-t border-gray-600 pt-2 mt-2">
        <p><strong>ID:</strong> {{ t.id }}</p>
        <p><strong>Variant:</strong> {{ t.variant }}</p>
        <p><strong>Title:</strong> {{ t.title }}</p>
        <p><strong>Description:</strong> {{ t.description }}</p>
      </div>
      <button 
        @click="testToast" 
        class="mt-2 px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-white"
      >
        Test Toast
      </button>
    </div>
  </div>
</template>

<script setup>
import { useToast } from '../composable/toastification/useToast';

const toast = useToast();

const testToast = () => {
  console.log('Testing toast...');
  toast.success('Test', 'This is a test toast!');
  console.log('Toast added. Current toasts:', toast.toasts.value);
};
</script>
