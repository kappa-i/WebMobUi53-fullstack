<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Store a newly created poll.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'question'               => 'required|string|max:500',
            'title'                  => 'nullable|string|max:255',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change'      => 'boolean',
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'is_draft'               => 'boolean',
            'options'                => 'required|array|min:2',
            'options.*.label'        => 'required|string|max:255',
        ]);

        $isDraft = $data['is_draft'] ?? true;

        $poll = $request->user()->polls()->create([
            'question'               => $data['question'],
            'title'                  => $data['title'] ?? null,
            'secret_token'           => Str::random(32),
            'is_draft'               => $isDraft,
            'allow_multiple_choices' => $data['allow_multiple_choices'] ?? false,
            'allow_vote_change'      => $data['allow_vote_change'] ?? false,
            'results_public'         => $data['results_public'] ?? false,
            'duration'               => $data['duration'] ?? null,
            'started_at'             => $isDraft ? null : now(),
            'ends_at'                => (!$isDraft && isset($data['duration']))
                                            ? now()->addSeconds($data['duration'])
                                            : null,
        ]);

        $poll->options()->createMany(
            array_map(fn($opt) => ['label' => $opt['label']], $data['options'])
        );

        return response()->json($poll->load('options'), 201);
    }


    /**
     * Update the specified poll.
     */
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $data = $request->validate([
            'question'               => 'required|string|max:500',
            'title'                  => 'nullable|string|max:255',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change'      => 'boolean',
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'options'                => 'required|array|min:2',
            'options.*.id'           => 'nullable|integer',
            'options.*.label'        => 'required|string|max:255',
        ]);

        $poll->update([
            'question'               => $data['question'],
            'title'                  => $data['title'] ?? null,
            'allow_multiple_choices' => $data['allow_multiple_choices'] ?? $poll->allow_multiple_choices,
            'allow_vote_change'      => $data['allow_vote_change'] ?? $poll->allow_vote_change,
            'results_public'         => $data['results_public'] ?? $poll->results_public,
            'duration'               => $data['duration'] ?? null,
        ]);

        $incomingIds = collect($data['options'])->pluck('id')->filter()->all();
        $poll->options()->whereNotIn('id', $incomingIds)->delete();

        foreach ($data['options'] as $opt) {
            if (!empty($opt['id'])) {
                $poll->options()->where('id', $opt['id'])->update(['label' => $opt['label']]);
            } else {
                $poll->options()->create(['label' => $opt['label']]);
            }
        }

        return response()->json($poll->load('options'));
    }

    /**
     * Start a draft poll.
     */
    public function start(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Poll is already started.'], 422);
        }

        $poll->update([
            'is_draft'   => false,
            'started_at' => now(),
            'ends_at'    => $poll->duration ? now()->addSeconds($poll->duration) : null,
        ]);

        return response()->json($poll);
    }

    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->orderBy('created_at', 'desc')->get();

        return $polls;
    }

    /**
     * Submit a vote on a poll.
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }
        if ($poll->is_draft) {
            return response()->json(['message' => 'Ce sondage n\'est pas encore ouvert.'], 422);
        }
        if ($poll->ends_at && $poll->ends_at < now()) {
            return response()->json(['message' => 'Ce sondage est terminé.'], 422);
        }

        $data = $request->validate([
            'option_ids'   => 'required|array|min:1',
            'option_ids.*' => 'integer',
        ]);

        if (!$poll->allow_multiple_choices && count($data['option_ids']) > 1) {
            return response()->json(['message' => 'Un seul choix autorisé.'], 422);
        }

        $validIds = $poll->options()->pluck('id')->all();
        foreach ($data['option_ids'] as $optId) {
            if (!in_array($optId, $validIds)) {
                return response()->json(['message' => 'Option invalide.'], 422);
            }
        }

        $alreadyVoted = $poll->votes()->where('user_id', $request->user()->id)->exists();
        if ($alreadyVoted && !$poll->allow_vote_change) {
            return response()->json(['message' => 'Vous avez déjà voté.'], 422);
        }

        $poll->votes()->where('user_id', $request->user()->id)->delete();

        foreach ($data['option_ids'] as $optId) {
            $poll->votes()->create([
                'user_id'        => $request->user()->id,
                'poll_option_id' => $optId,
            ]);
        }

        return response()->json(['message' => 'Vote enregistré.'], 201);
    }

    /**
     * Get results for a poll.
     */
    public function results(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $isOwner = $request->user() && $request->user()->id === $poll->user_id;

        if (!$poll->results_public && !$isOwner) {
            return response()->json(['message' => 'Résultats privés.'], 403);
        }

        $options = $poll->options()->withCount('votes')->get();

        return response()->json([
            'options' => $options,
            'total'   => $poll->votes()->count(),
        ]);
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $userId = $request->user()?->id;
        $poll->user_has_voted = $userId
            ? $poll->votes()->where('user_id', $userId)->exists()
            : false;

        return $poll;
    }

    /**
     * Remove the specified poll.
     */
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }
}
