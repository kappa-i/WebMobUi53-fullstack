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

  async function deletePoll(id) {
    await fetchApi({ url: '/polls/' + id, method: 'DELETE' });
    polls.value = polls.value.filter(p => p.id !== id);
  }

  return { polls, setPolls, createPoll, deletePoll };
}
