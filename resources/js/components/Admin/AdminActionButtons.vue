<template>
  <div class="flex justify-center gap-2">
    <slot name="actions" :item="item">
      <!-- Edit Button -->
      <a 
        v-if="showEdit"
        :href="editUrl" 
        class="action-button edit-button"
        :title="editTitle"
      >
        <font-awesome-icon icon="fa-solid fa-pencil" />
      </a>
      
      <!-- Delete Button -->
      <button 
        v-if="showDelete"
        @click="$emit('delete', item)" 
        class="action-button delete-button"
        :title="deleteTitle"
        :disabled="deleteDisabled"
      >
        <font-awesome-icon icon="fa-solid fa-trash" />
      </button>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Props {
  item: any;
  editRoute?: string;
  editField?: string;
  showEdit?: boolean;
  showDelete?: boolean;
  editTitle?: string;
  deleteTitle?: string;
  deleteDisabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showEdit: true,
  showDelete: true,
  editTitle: 'Edit',
  deleteTitle: 'Delete',
  deleteDisabled: false,
  editField: 'id'
});

defineEmits<{
  delete: [item: any];
}>();

const editUrl = computed(() => {
  if (props.editRoute && props.item) {
    return props.editRoute.replace(':id', props.item[props.editField]);
  }
  return '#';
});
</script>

<style scoped>
@import '@/../css/admin.css';
</style>
