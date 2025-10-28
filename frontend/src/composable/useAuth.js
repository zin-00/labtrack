import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import router from '@/router'
import { useApiUrl } from '../api/api'

const { api, getAuthHeader } = useApiUrl()

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const currentStep = ref('email')
  const emailVerified = ref(false)
  const userInfo = ref(null)
  const isVerifyingEmail = ref(false)
  const token = ref(null)

  const toast = useToast()
  const step = ref(1)
  const email = ref('')
  const password = ref('')
  const remember = ref(false)
  const processing = ref(false)
  const errors = ref({})

  const isModalOpen = ref(false)
  const isLoading = ref(false)
  const isSuccess = ref(false);
  const errorMessage = ref(false);

const requestData = reactive({
  id_number: '',
  fullname: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
});

  const registerForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    remember: false,
    isLoading: false,
    errors: {},
    processing: false
  })

  const setUser = (userData) => {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const loadUser = () => {
    const data = localStorage.getItem('user')
    if (data) {
      user.value = JSON.parse(data)
    }
  }

  const clearUser = () => {
    user.value = null
    localStorage.removeItem('user')
  }

  const resetForm = () => {
    requestData.id_number = ''
    requestData.fullname = ''
    requestData.email = ''
    requestData.password = ''
    requestData.password_confirmation = ''
    requestData.role = ''

    isSuccess.value = false;
    errorMessage.value = false;
    isLoading.value = false;
  }

  const closeModal = async () => {
    isModalOpen.value = false;

    setTimeout(() => {
      resetForm();
      isModalOpen.value = false;
    }, 300)
  }
  const checkEmail = async () => {
    errors.value = {}
    processing.value = true
    try {
      const res = await axios.post(`${api}/auth/check-email`, { email: email.value }, getAuthHeader() )
      if (res.data.exists) {
        step.value = 2
      } else {
        errors.value.email = 'Email not found'
      }
    } catch (e) {
      toast.error('Error checking email')
    } finally {
      processing.value = false
    }
  }
  const login = async () => {
    errors.value = {}
    processing.value = true
    try {
      const response = await axios.post(`${api}/auth/login`, {
        email: email.value,
        password: password.value,
        remember: remember.value
      }, getAuthHeader())
      
      const data = response.data.user
      const token = response.data.token
      
      toast.success(response.data.message || 'Login successful!')
      
      if (data && token) {
        setUser(data)
        setToken(token)
        router.push({ name: 'dashboard' })
      }
    } catch (error) {
      console.error('Login failed:', error)
      toast.error('Login failed. Please check your credentials.')
    } finally{
      processing.value = false
    }
  }

  const register = async () => {
    try {
      const response = await axios.post(`${api}/auth/register`, {
        name: registerForm.name,
        email: registerForm.email,
        password: registerForm.password,
        password_confirmation: registerForm.password_confirmation
      }, getAuthHeader())
      
      const data = response.data.message

      if (data) {
        toast.success(data)
        router.push({ name: 'login' })
      }
    } catch (error) {
      console.error('Registration failed:', error)
      toast.error('Registration failed. Please try again.')
    }
  }

  const submitRequest = async () => {
  errorMessage.value = '';
  isLoading.value = true;

  try {
    const response = await axios.post(`${api}/request-access`, {
      id_number: requestData.id_number,
      fullname: requestData.fullname,
      email: requestData.email,
      password: requestData.password,
      password_confirmation: requestData.password_confirmation,
      role: requestData.role
    });

    if (response.data.success) {
      isSuccess.value = true;
      toast.success(response.data.message);
    } else {
      errorMessage.value = response.data.message;
      if (response.data.errors) {
        // Handle field-specific errors
        for (const [field, errors] of Object.entries(response.data.errors)) {
          toast.error(`${field}: ${errors.join(', ')}`);
        }
      }
    }
  } catch (error) {
    console.error('Request failed:', error);
    if (error.response) {
      // Server responded with error status
      if (error.response.status === 422) {
        // Validation errors
        const errors = error.response.data.errors;
        errorMessage.value = Object.values(errors).flat().join(', ');
      } else {
        errorMessage.value = error.response.data.message || 'Request failed. Please try again.';
      }
    } else {
      errorMessage.value = 'Network error. Please check your connection.';
    }
    toast.error(errorMessage.value);
  } finally {
    isLoading.value = false;
  }
}


  const logout = async () => {
    try {
      await axios.delete(`${api}/auth/logout`, getAuthHeader())
      clearUser()
      clearToken()
      router.push({ name: 'login' })
    } catch (error) {
      console.error('Logout failed:', error)
    }
  }

  // Token management (keeping your original token store)
  const setToken = (tokenData) => {
    token.value = tokenData
    localStorage.setItem('auth_token', tokenData)
  }

  const loadToken = () => {
    const data = localStorage.getItem('auth_token')
    if (data) {
      token.value = data
    }
  }

  const clearToken = () => {
    token.value = null
    localStorage.removeItem('auth_token')
  }

  const clearForm = () => {
    registerForm.name = ''
    registerForm.email = ''
    registerForm.password = ''
    registerForm.password_confirmation = ''
    registerForm.isLoading = false
    registerForm.errors = {}
  }

  return {
    // State
    user,
    currentStep,
    emailVerified,
    userInfo,
    isVerifyingEmail,
    token,
    registerForm,
    errors,
    step,
    email,
    password,
    remember,
    processing,
    toast,
    isModalOpen,
    isLoading,
    isSuccess,
    errorMessage,
    requestData,
    
    // Methods
    setUser,
    loadUser,
    clearUser,
    checkEmail,
    login,
    register,
    logout,
    setToken,
    loadToken,
    clearToken,
    clearForm,
    closeModal,
    submitRequest,
  }
})