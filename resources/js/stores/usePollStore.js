import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  async function createPoll(data) {
    const created = await fetchApi({ url: '/polls', data });
    polls.value.unshift(created);
    return created;
  }

  async function updatePoll(id, data) {
    const updated = await fetchApi({ url: '/polls/' + id, data, method: 'PUT' });
    const index = polls.value.findIndex(p => p.id === id);
    if (index !== -1) polls.value[index] = updated;
    return updated;
  }

  async function deletePoll(id) {
    await fetchApi({ url: '/polls/' + id, method: 'DELETE' });
    polls.value = polls.value.filter(p => p.id !== id);
  }

  return { polls, setPolls, createPoll, updatePoll, deletePoll };
}
