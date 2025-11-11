<script setup>
import { useToast } from './composable/toastification/useToast';
import ToastContainer from './components/toastification/ToastContainer.vue';

const { toasts, removeToast } = useToast();
</script>
<template>
  <RouterView />
  <ToastContainer :toasts="toasts" @remove="removeToast" />
</template>
