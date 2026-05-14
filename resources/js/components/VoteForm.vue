<script setup>
import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const props = defineProps({
  poll:     { type: Object,  required: true },
  token:    { type: String,  required: true },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['voted']);

const { fetchApi } = useFetchApi();

const selected = ref([]);
const submitting = ref(false);
const error = ref(null);

function toggleOption(id) {
  if (props.poll.allow_multiple_choices) {
    const idx = selected.value.indexOf(id);
    if (idx === -1) selected.value.push(id);
    else selected.value.splice(idx, 1);
  } else {
    selected.value = [id];
  }
}

async function submit() {
  if (selected.value.length === 0) {
    error.value = 'Veuillez sélectionner une option.';
    return;
  }

  submitting.value = true;
  error.value = null;

  try {
    await fetchApi({
      url:    '/polls/' + props.token + '/votes',
      method: 'POST',
      data:   { option_ids: selected.value },
    });
    emit('voted');
  } catch (err) {
    error.value = err?.data?.message ?? 'Une erreur est survenue.';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div>
    <div class="space-y-2 mb-4">
      <label
        v-for="option in poll.options"
        :key="option.id"
        class="flex items-center gap-3 p-3 border rounded"
        :class="[
          disabled ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'cursor-pointer hover:bg-gray-50',
          { 'border-blue-500 bg-blue-50': selected.includes(option.id) }
        ]"
      >
        <input
          v-if="poll.allow_multiple_choices"
          type="checkbox"
          :value="option.id"
          :checked="selected.includes(option.id)"
          :disabled="disabled"
          @change="!disabled && toggleOption(option.id)"
          class="rounded"
        />
        <input
          v-else
          type="radio"
          :value="option.id"
          :checked="selected.includes(option.id)"
          :disabled="disabled"
          @change="!disabled && toggleOption(option.id)"
        />
        <span class="text-sm">{{ option.label }}</span>
      </label>
    </div>

    <p v-if="error" class="mb-3 text-sm text-red-600">{{ error }}</p>

    <button
      v-if="!disabled"
      @click="submit"
      :disabled="submitting"
      class="bg-blue-600 text-white px-5 py-2 rounded text-sm hover:bg-blue-700 disabled:opacity-50"
    >
      {{ submitting ? 'Envoi…' : 'Voter' }}
    </button>
  </div>
</template>
