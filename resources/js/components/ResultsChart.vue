<script setup>
import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';
import { usePolling } from '@/composables/usePolling';

const props = defineProps({
  token: { type: String, required: true },
});

const { fetchApi } = useFetchApi();

const results = ref(null);

async function fetchResults() {
  try {
    results.value = await fetchApi({ url: '/polls/' + props.token + '/results' });
  } catch {
    // résultats non accessibles (privés ou sondage inexistant)
  }
}

fetchResults();
usePolling(fetchResults, 5000);
</script>

<template>
  <div v-if="results" class="mt-6">
    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
      Résultats — {{ results.total }} vote{{ results.total !== 1 ? 's' : '' }}
    </h3>

    <div class="space-y-3">
      <div v-for="option in results.options" :key="option.id">
        <div class="flex justify-between text-sm mb-1">
          <span>{{ option.label }}</span>
          <span class="text-gray-500">{{ option.votes_count }} ({{ results.total > 0 ? Math.round(option.votes_count / results.total * 100) : 0 }}%)</span>
        </div>
        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-blue-500 rounded-full transition-all duration-500"
            :style="{ width: results.total > 0 ? (option.votes_count / results.total * 100) + '%' : '0%' }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>
