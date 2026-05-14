<script setup>
import { usePollStore } from '@/stores/usePollStore';

const emit = defineEmits(['edit']);

const { polls, deletePoll } = usePollStore();
</script>

<template>
  <p v-if="polls.length === 0">Aucun sondage.</p>

  <table v-else class="w-full border-collapse text-left text-sm">
    <thead>
      <tr class="bg-gray-100">
        <th class="border px-3 py-2">Actions</th>
        <th class="border px-3 py-2">ID</th>
        <th class="border px-3 py-2">Titre</th>
        <th class="border px-3 py-2">Question</th>
        <th class="border px-3 py-2">Brouillon</th>
        <th class="border px-3 py-2">Debut</th>
        <th class="border px-3 py-2">Fin</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="poll in polls" :key="poll.id" class="hover:bg-gray-50">
        <td class="border px-3 py-2 flex gap-2">
          <button @click="emit('edit', poll)" class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600">Modifier</button>
          <button @click="deletePoll(poll.id)" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">Supprimer</button>
        </td>
        <td class="border px-3 py-2">{{ poll.id }}</td>
        <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
        <td class="border px-3 py-2">{{ poll.question }}</td>
        <td class="border px-3 py-2">{{ poll.is_draft ? 'Oui' : 'Non' }}</td>
        <td class="border px-3 py-2">{{ poll.started_at || '-' }}</td>
        <td class="border px-3 py-2">{{ poll.ends_at || '-' }}</td>
      </tr>
    </tbody>
  </table>
</template>
