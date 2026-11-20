<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">レシピ一覧</h2>
            <a href="{{ route('dashboard.recipes.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                レシピを作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')

            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($recipes as $recipe)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <a href="{{ route('dashboard.recipes.show', $recipe) }}" class="font-semibold text-lg text-gray-800 hover:underline">
                            {{ $recipe->name }}
                        </a>
                        <p class="mt-1 text-sm text-gray-600">
                            作成日: {{ $recipe->creation_date->format('Y/m/d') }} / 食材 {{ $recipe->foods->count() }} 件
                        </p>
                        <p class="text-sm text-gray-600">
                            合計 {{ number_format($recipe->totalCalories(), 1) }} kcal
                        </p>
                    </div>
                @empty
                    <p class="text-gray-600">レシピがありません。</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $recipes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
