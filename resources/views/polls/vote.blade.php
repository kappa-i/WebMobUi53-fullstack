<x-vue-app-layout>
    <x-slot:scripts>
        @vite(['resources/js/poll-vote.js'])
    </x-slot>

    <x-slot:title>
        Sondage
    </x-slot>

    <div
        id="app"
        data-props='@json([
            "token" => $token,
            "user"  => $user,
        ])'
    ></div>
</x-vue-app-layout>
