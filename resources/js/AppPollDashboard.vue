<script setup>
import { ref } from 'vue';
import PollTable from './components/PollTable.vue';
import PollForm from './components/PollForm.vue';
import { usePollStore } from '@/stores/usePollStore';

const props = defineProps({
  polls: { type: Array, default: () => [] },
  loginUrl: { type: String, default: null },
  username: { type: String, default: null },
});

const { setPolls } = usePollStore();
setPolls(props.polls);

const view = ref('list');       // 'list' | 'create' | 'edit'
const editingPoll = ref(null);

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
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">Mes sondages</h1>
      <button
        v-if="view === 'list'"
        @click="openCreate"
        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
      >
        + Nouveau sondage
      </button>
    </div>

    <PollTable
      v-if="view === 'list'"
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
