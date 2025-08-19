<template>
  <div class="login-container">
    <div class="login-card">
      <h2 class="login-title">Sign In</h2>
      
      <form class="login-form" @submit.prevent="handleSubmit">
        <input
          id="email"
          v-model="form.email"
          name="email"
          type="email"
          autocomplete="email"
          required
          class="form-input"
          placeholder="Email"
          :class="{ 'input-error': errors.email }"
        />
        
        <input
          id="password"
          v-model="form.password"
          name="password"
          type="password"
          autocomplete="current-password"
          required
          class="form-input"
          placeholder="Password"
          :class="{ 'input-error': errors.password }"
        />

        <div v-if="errors.email" class="error-message">
          {{ errors.email }}
        </div>

        <button
          type="submit"
          :disabled="processing"
          class="login-button"
        >
          {{ processing ? 'Signing in...' : 'Sign in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const form = reactive({
  email: '',
  password: '',
})

const errors = ref<Record<string, string>>({})
const processing = ref(false)

const handleSubmit = async () => {
  processing.value = true
  errors.value = {}

  router.post(route('admin.login.post'), form, {
    onError: (errorBag) => {
      errors.value = errorBag
    },
    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-4);
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  padding: var(--spacing-10);
}

.login-title {
  font-size: var(--text-2xl);
  font-weight: var(--font-semibold);
  color: var(--text-color);
  text-align: center;
  margin: 0 0 var(--spacing-8) 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.form-input {
  width: 100%;
  padding: var(--spacing-3);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  background: white;
  font-size: var(--text-base);
  color: var(--text-color);
  transition: border-color var(--transition-normal);
  outline: none;
  box-sizing: border-box;
}

.form-input:focus {
  border-color: var(--primary-color);
}

.form-input::placeholder {
  color: var(--text-muted);
}

.input-error {
  border-color: var(--danger-color);
}

.error-message {
  color: var(--danger-color);
  font-size: var(--text-sm);
  margin-top: calc(var(--spacing-4) * -1);
}

.login-button {
  width: 100%;
  padding: var(--spacing-3);
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-size: var(--text-base);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: opacity var(--transition-normal);
  outline: none;
}

.login-button:hover:not(:disabled) {
  opacity: 0.9;
}

.login-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .login-card {
    padding: var(--spacing-6);
    margin: var(--spacing-4);
  }
}
</style>
