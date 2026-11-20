<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">食品を編集</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @include('dashboard.foods.partials.form', [
                        'action' => route('dashboard.foods.update', $food),
                        'method' => 'PUT',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
