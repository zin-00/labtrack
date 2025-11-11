import { ref } from 'vue';

// Global shared state for toasts
const toasts = ref([]);
let toastIdCounter = 0;

export function useToast() {
  const addToast = (variant, title, description, duration = 5000) => {
    // Validate that at least title or description exists
    if (!title && !description) {
      console.warn('Toast not added: Both title and description are empty');
      return;
    }

    const id = ++toastIdCounter;
    const toast = {
      id,
      variant: variant || 'default',
      title,
      description,
      duration
    };
    
    console.log('Adding toast:', toast);
    console.log('Current toasts before add:', toasts.value);
    toasts.value.push(toast);
    console.log('Current toasts after add:', toasts.value);
    console.log('Toasts array length:', toasts.value.length);

    // Auto-remove after duration
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
  };

  const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index > -1) {
      toasts.value.splice(index, 1);
    }
  };

  // Convenience methods
  const success = (title, description, duration) => {
    addToast('success', title, description, duration);
  };

  const error = (title, description, duration) => {
    addToast('error', title, description, duration);
  };

  const warning = (title, description, duration) => {
    addToast('warning', title, description, duration);
  };

  const info = (title, description, duration) => {
    addToast('info', title, description, duration);
  };

  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info
  };
}