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
     * Display the specified poll by its secret token.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

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
