<script setup>
import { reactive, ref } from 'vue';
import { usePollStore } from '@/stores/usePollStore';

const emit = defineEmits(['saved', 'cancel']);

const { createPoll } = usePollStore();

const form = reactive({
  title: '',
  question: '',
  options: [{ label: '' }, { label: '' }],
  allow_multiple_choices: false,
  results_public: false,
  duration: '',
  is_draft: true,
});

const errors = ref({});
const submitting = ref(false);

function addOption() {
  form.options.push({ label: '' });
}

function removeOption(index) {
  if (form.options.length > 2) {
    form.options.splice(index, 1);
  }
}

function validate() {
  const e = {};
  if (!form.question.trim()) e.question = 'La question est obligatoire.';
  const filled = form.options.filter(o => o.label.trim());
  if (filled.length < 2) e.options = 'Au moins 2 options sont requises.';
  errors.value = e;
  return Object.keys(e).length === 0;
}

async function submit() {
  if (!validate()) return;
  submitting.value = true;
  errors.value = {};

  const payload = {
    ...form,
    options: form.options.filter(o => o.label.trim()),
    duration: form.duration ? parseInt(form.duration) : null,
  };

  try {
    await createPoll(payload);
    emit('saved');
  } catch (err) {
    if (err?.data?.errors) {
      errors.value = err.data.errors;
    } else {
      errors.value = { general: 'Une erreur est survenue.' };
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="max-w-xl mx-auto p-4">
    <h2 class="text-xl font-semibold mb-4">Nouveau sondage</h2>

    <p v-if="errors.general" class="mb-3 text-sm text-red-600">{{ errors.general }}</p>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Titre <span class="text-gray-400">(optionnel)</span></label>
      <input v-model="form.title" type="text" class="w-full border rounded px-3 py-2 text-sm" placeholder="Ex: Sondage du vendredi" />
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Question <span class="text-red-500">*</span></label>
      <input v-model="form.question" type="text" class="w-full border rounded px-3 py-2 text-sm" :class="{ 'border-red-500': errors.question }" placeholder="Ex: Quel est votre langage préféré ?" />
      <p v-if="errors.question" class="mt-1 text-xs text-red-600">{{ errors.question }}</p>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Options <span class="text-red-500">*</span></label>
      <div v-for="(option, index) in form.options" :key="index" class="flex gap-2 mb-2">
        <input v-model="option.label" type="text" class="flex-1 border rounded px-3 py-2 text-sm" :placeholder="'Option ' + (index + 1)" />
        <button type="button" @click="removeOption(index)" :disabled="form.options.length <= 2" class="px-2 text-red-500 disabled:opacity-30">✕</button>
      </div>
      <p v-if="errors.options" class="mt-1 text-xs text-red-600">{{ errors.options }}</p>
      <button type="button" @click="addOption" class="mt-1 text-sm text-blue-600 hover:underline">+ Ajouter une option</button>
    </div>

    <div class="mb-4 space-y-2">
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.allow_multiple_choices" type="checkbox" class="rounded" />
        Autoriser plusieurs choix
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.results_public" type="checkbox" class="rounded" />
        Résultats publics
      </label>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Durée <span class="text-gray-400">(en secondes, optionnel)</span></label>
      <input v-model="form.duration" type="number" min="1" class="w-full border rounded px-3 py-2 text-sm" placeholder="Ex: 3600 pour 1 heure" />
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium mb-1">Mode de lancement</label>
      <div class="flex gap-4 text-sm">
        <label class="flex items-center gap-2">
          <input v-model="form.is_draft" type="radio" :value="true" />
          Enregistrer en brouillon
        </label>
        <label class="flex items-center gap-2">
          <input v-model="form.is_draft" type="radio" :value="false" />
          Lancer immédiatement
        </label>
      </div>
    </div>

    <div class="flex gap-3">
      <button @click="submit" :disabled="submitting" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 disabled:opacity-50">
        {{ submitting ? 'Enregistrement…' : 'Créer le sondage' }}
      </button>
      <button @click="emit('cancel')" type="button" class="px-4 py-2 rounded text-sm border hover:bg-gray-50">
        Annuler
      </button>
    </div>
  </div>
</template>
