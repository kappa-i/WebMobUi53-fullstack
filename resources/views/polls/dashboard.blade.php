<x-vue-app-layout>
    <x-slot:scripts>
        @vite(['resources/js/poll-dashboard.js'])
    </x-slot>

    <x-slot:title>
        Sondages
    </x-slot>

    <div
        id="app"
        data-props='@json([
            "polls" => $polls,
            "loginUrl" => route("login"),
            "username" => "test name"
        ])'
    ></div>
</x-vue-app-layout>
