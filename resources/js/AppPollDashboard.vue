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

  const showForm = ref(false);
</script>

<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">Mes sondages</h1>
      <button
        v-if="!showForm"
        @click="showForm = true"
        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
      >
        + Nouveau sondage
      </button>
    </div>

    <PollForm
      v-if="showForm"
      @saved="showForm = false"
      @cancel="showForm = false"
    />

    <PollTable v-else />
  </div>
</template>
