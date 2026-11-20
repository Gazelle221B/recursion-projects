<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">食品一覧</h2>
            <a href="{{ route('dashboard.foods.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                食品を作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('dashboard.foods.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <x-input-label for="name" value="名前で検索" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block"
                                          :value="request('name')" />
                        </div>
                        <div>
                            <x-input-label for="food_type" value="食品の種類" />
                            <select id="food_type" name="food_type" class="mt-1 block border-gray-300 rounded-md shadow-sm">
                                <option value="">すべて</option>
                                @foreach ($foodTypes as $type)
                                    <option value="{{ $type->name }}" @selected(request('food_type') === $type->name)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tags" value="タグ（カンマ区切り）" />
                            <x-text-input id="tags" name="tags" type="text" class="mt-1 block"
                                          :value="request('tags')" />
                        </div>
                        <x-primary-button>検索</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($foods as $food)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <a href="{{ route('dashboard.foods.show', $food) }}" class="font-semibold text-lg text-gray-800 hover:underline">
                            {{ $food->name }}
                        </a>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $food->food_type_name ?? '未分類' }} / {{ number_format($food->getTotalCalories(), 1) }} kcal（{{ $food->grams }}gあたり）
                        </p>
                        <p class="text-sm text-gray-600">
                            たんぱく質 {{ $food->protein }}g ・ 炭水化物 {{ $food->carbs }}g ・ 脂質 {{ $food->fat }}g
                        </p>
                        @if ($food->tags->isNotEmpty())
                            <p class="mt-2 flex flex-wrap gap-1">
                                @foreach ($food->tags as $tag)
                                    <span class="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800">{{ $tag->name }}</span>
                                @endforeach
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-600">食品がありません。</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $foods->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
