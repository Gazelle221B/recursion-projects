<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $recipe->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600">作成日: {{ $recipe->creation_date->format('Y/m/d') }}</p>

                    <h3 class="font-semibold text-gray-800">食材</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($recipe->foods as $food)
                            <li class="py-2 flex items-center justify-between">
                                <a href="{{ route('dashboard.foods.show', $food) }}" class="text-gray-800 hover:underline">{{ $food->name }}</a>
                                <span class="text-sm text-gray-500">
                                    {{ number_format($food->getTotalCalories(), 1) }} kcal（{{ $food->grams }}gあたり）
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="text-lg">
                        合計カロリー:
                        <span class="font-semibold">{{ number_format($recipe->totalCalories(), 1) }} kcal</span>
                    </p>

                    <div class="flex items-center gap-4 pt-2">
                        <a href="{{ route('dashboard.recipes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">一覧に戻る</a>
                        <a href="{{ route('dashboard.recipes.edit', $recipe) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            編集
                        </a>
                        <form method="POST" action="{{ route('dashboard.recipes.destroy', $recipe) }}"
                              onsubmit="return confirm('このレシピを削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>削除</x-danger-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
