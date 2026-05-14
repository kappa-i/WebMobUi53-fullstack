<script setup>
import { ref, onMounted } from 'vue';
import PollTable from './components/PollTable.vue';
import PollForm from './components/PollForm.vue';
import { usePollStore } from '@/stores/usePollStore';

const { fetchPolls, loading } = usePollStore();

const view = ref('list');
const editingPoll = ref(null);
const fetchError = ref(null);

onMounted(async () => {
  try {
    await fetchPolls();
  } catch {
    fetchError.value = 'Impossible de charger les sondages. Veuillez rafraîchir la page.';
  }
});

function openCreate() {
  editingPoll.value = null;
  view.value = 'create';
}

function openEdit(poll) {
  editingPoll.value = poll;
  view.value = 'edit';
}

function backToList() {
  editingPoll.value = null;
  view.value = 'list';
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <a href="/" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
      ← Retour à l'accueil
    </a>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Mes sondages</h1>
      <button
        v-if="view === 'list'"
        @click="openCreate"
        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
      >
        + Nouveau sondage
      </button>
    </div>

    <div v-if="fetchError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
      {{ fetchError }}
    </div>

    <div v-else-if="loading" class="text-gray-500 text-sm">Chargement…</div>

    <PollTable
      v-else-if="view === 'list'"
      @edit="openEdit"
    />

    <PollForm
      v-else
      :poll="editingPoll"
      @saved="backToList"
      @cancel="backToList"
    />
  </div>
</template>
