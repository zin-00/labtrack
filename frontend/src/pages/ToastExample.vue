<template>
  <div class="p-8 space-y-4">
    <h1 class="text-2xl font-bold mb-6">Toast Examples</h1>
    
    <div class="space-y-3">
      <button
        @click="showSuccess"
        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
      >
        Show Success Toast
      </button>

      <button
        @click="showError"
        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 ml-2"
      >
        Show Error Toast
      </button>

      <button
        @click="showWarning"
        class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 ml-2"
      >
        Show Warning Toast
      </button>

      <button
        @click="showInfo"
        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 ml-2"
      >
        Show Info Toast
      </button>

      <button
        @click="showMultiple"
        class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 ml-2"
      >
        Show Multiple Toasts
      </button>
    </div>
  </div>
</template>

<script setup>
import { useToast } from '../composable/toastification/useToast';

const toast = useToast();

const showSuccess = () => {
  toast.success('Success!', 'Your operation completed successfully');
};

const showError = () => {
  toast.error('Error!', 'Something went wrong with your request');
};

const showWarning = () => {
  toast.warning('Warning!', 'Please review your inputs before proceeding');
};

const showInfo = () => {
  toast.info('Information', 'A new update is available for download');
};

const showMultiple = () => {
  toast.success('First Toast', 'This is the first toast');
  setTimeout(() => {
    toast.info('Second Toast', 'This is the second toast');
  }, 500);
  setTimeout(() => {
    toast.warning('Third Toast', 'This is the third toast');
  }, 1000);
};
</script>
