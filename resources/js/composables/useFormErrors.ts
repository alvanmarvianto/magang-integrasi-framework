import { ref, computed } from 'vue';

export function useFormErrors() {
  const errors = ref<Record<string, string | string[]>>({});

  const hasErrors = computed(() => Object.keys(errors.value).length > 0);

  const getFieldError = (fieldName: string): string | undefined => {
    const error = errors.value[fieldName];
    if (Array.isArray(error)) {
      return error[0]; // Return first error if multiple
    }
    return error;
  };

  const hasFieldError = (fieldName: string): boolean => {
    return fieldName in errors.value;
  };

  const setErrors = (newErrors: Record<string, string | string[]>) => {
    errors.value = newErrors;
  };

  const clearErrors = () => {
    errors.value = {};
  };

  const clearFieldError = (fieldName: string) => {
    delete errors.value[fieldName];
  };

  const getErrorMessages = (): string[] => {
    const messages: string[] = [];
    Object.values(errors.value).forEach(error => {
      if (Array.isArray(error)) {
        messages.push(...error);
      } else {
        messages.push(error);
      }
    });
    return messages;
  };

  return {
    errors,
    hasErrors,
    getFieldError,
    hasFieldError,
    setErrors,
    clearErrors,
    clearFieldError,
    getErrorMessages,
  };
}
