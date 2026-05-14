<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePollStore } from '@/stores/usePollStore';

const emit = defineEmits(['edit']);

const { polls, startPoll, deletePoll } = usePollStore();

const copiedId = ref(null);
const now = ref(Date.now());
let timer;
onMounted(() => { timer = setInterval(() => { now.value = Date.now(); }, 30000); });
onUnmounted(() => clearInterval(timer));

function pollStatus(poll) {
  if (poll.is_draft) return 'brouillon';
  if (poll.ends_at && new Date(poll.ends_at).getTime() < now.value) return 'terminé';
  return 'actif';
}

const statusStyle = {
  brouillon: 'bg-yellow-100 text-yellow-800',
  actif:     'bg-green-100 text-green-800',
  terminé:   'bg-gray-100 text-gray-600',
};

function copyLink(poll) {
  navigator.clipboard.writeText(window.location.origin + '/polls/' + poll.secret_token);
  copiedId.value = poll.id;
  setTimeout(() => copiedId.value = null, 2000);
}
</script>

<template>
  <p v-if="polls.length === 0" class="text-gray-500 text-sm">Aucun sondage.</p>

  <div v-else class="overflow-x-auto rounded border">
    <table class="w-full border-collapse text-left text-sm min-w-[500px]">
      <thead>
        <tr class="bg-gray-100">
          <th class="px-3 py-2 border-b">Question</th>
          <th class="px-3 py-2 border-b">Statut</th>
          <th class="px-3 py-2 border-b">Fin</th>
          <th class="px-3 py-2 border-b">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="poll in polls" :key="poll.id" class="hover:bg-gray-50 border-b last:border-0">
          <td class="px-3 py-2">
            <div class="font-medium">{{ poll.title || poll.question }}</div>
            <div v-if="poll.title" class="text-gray-400 text-xs">{{ poll.question }}</div>
          </td>
          <td class="px-3 py-2">
            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusStyle[pollStatus(poll)]">
              {{ pollStatus(poll) }}
            </span>
          </td>
          <td class="px-3 py-2 text-gray-500 text-xs">
            {{ poll.ends_at ? new Date(poll.ends_at).toLocaleString('fr-CH') : '—' }}
          </td>
          <td class="px-3 py-2">
            <div class="flex gap-1 flex-wrap">
              <button v-if="poll.is_draft" @click="startPoll(poll.id)" class="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600">Démarrer</button>
              <a v-if="!poll.is_draft" :href="'/polls/' + poll.secret_token" class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600">Voter</a>
              <a v-if="pollStatus(poll) === 'terminé'" :href="'/polls/' + poll.secret_token" class="bg-gray-500 text-white px-2 py-1 rounded text-xs hover:bg-gray-600">Résultats</a>
              <button v-if="!poll.is_draft" @click="copyLink(poll)" class="bg-gray-400 text-white px-2 py-1 rounded text-xs hover:bg-gray-500">
                {{ copiedId === poll.id ? 'Copié !' : 'Lien' }}
              </button>
              <button @click="emit('edit', poll)" class="bg-gray-400 text-white px-2 py-1 rounded text-xs hover:bg-gray-500">Modifier</button>
              <button @click="deletePoll(poll.id)" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">Supprimer</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
