<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';
import VoteForm from './components/VoteForm.vue';
import ResultsChart from './components/ResultsChart.vue';

const props = defineProps({
  token: { type: String, required: true },
  user:  { type: Object, default: null },
});

const { fetchApi } = useFetchApi();

const poll    = ref(null);
const loading = ref(true);
const error   = ref(null);
const voted   = ref(false);
const now     = ref(Date.now());
let timer;
onMounted(() => { timer = setInterval(() => { now.value = Date.now(); }, 30000); });
onUnmounted(() => clearInterval(timer));

onMounted(async () => {
  try {
    const data = await fetchApi({ url: '/polls/' + props.token });
    poll.value = data;
    voted.value = data.user_has_voted ?? false;
  } catch (err) {
    error.value = err?.data?.message ?? 'Sondage introuvable.';
  } finally {
    loading.value = false;
  }
});

const isEnded = computed(() => {
  if (!poll.value?.ends_at) return false;
  return new Date(poll.value.ends_at).getTime() < now.value;
});

const canVote = computed(() =>
  props.user &&
  poll.value &&
  !poll.value.is_draft &&
  !isEnded.value
);

const showResults = computed(() =>
  poll.value &&
  (poll.value.results_public || (props.user && props.user.id === poll.value.user_id))
);
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-xl mx-auto px-4 py-8">
      <a href="/polls/dashboard" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        ← Retour au dashboard
      </a>

      <div v-if="loading" class="text-gray-500 text-sm">Chargement…</div>

      <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded text-red-700">
        {{ error }}
      </div>

      <template v-else-if="poll">
        <h1 class="text-2xl font-bold mb-1">{{ poll.title || poll.question }}</h1>
        <p v-if="poll.title" class="text-gray-600 mb-6">{{ poll.question }}</p>

        <!-- Brouillon -->
        <div v-if="poll.is_draft" class="p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
          Ce sondage n'est pas encore ouvert.
        </div>

        <!-- Terminé -->
        <div v-else-if="isEnded" class="p-4 bg-gray-100 border rounded text-gray-600 text-sm mb-6">
          Ce sondage est terminé — il n'est plus possible de voter.
        </div>

        <template v-else>
          <!-- Vote déjà soumis -->
          <div v-if="voted" class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-800 text-sm">
            Votre vote a été enregistré.
          </div>

          <!-- Formulaire de vote -->
          <VoteForm
            v-if="canVote"
            :poll="poll"
            :token="token"
            :disabled="voted"
            @voted="voted = true"
          />

          <!-- Non connecté -->
          <div v-else-if="!user" class="p-3 bg-blue-50 border border-blue-200 rounded text-blue-700 text-sm">
            <a href="/auth/login" class="underline font-medium">Connectez-vous</a> pour voter.
          </div>
        </template>

        <!-- Résultats -->
        <ResultsChart v-if="showResults" :token="token" />

        <!-- Résultats privés -->
        <p v-else-if="!poll.is_draft" class="mt-6 text-sm text-gray-400 italic">
          Les résultats sont privés.
        </p>
      </template>
    </div>
  </div>
</template>
