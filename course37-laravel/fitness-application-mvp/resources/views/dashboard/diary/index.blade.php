<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">日誌一覧</h2>
            <a href="{{ route('dashboard.diary-entries.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                日誌を作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <li class="p-4 flex items-center justify-between">
                            <div>
                                <a href="{{ route('dashboard.diary-entries.show', $entry) }}" class="font-medium text-gray-800 hover:underline">
                                    {{ $entry->date->format('Y/m/d (D)') }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    食品 {{ $entry->foodItems->count() }} 件 / レシピ {{ $entry->recipeItems->count() }} 件
                                </p>
                            </div>
                            <span class="text-sm font-medium text-gray-700">
                                合計 {{ number_format($entry->totalCalories(), 1) }} kcal
                            </span>
                        </li>
                    @empty
                        <li class="p-4 text-gray-600">日誌がありません。</li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-6">
                {{ $entries->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
