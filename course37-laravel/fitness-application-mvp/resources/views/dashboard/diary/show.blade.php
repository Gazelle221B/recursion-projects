<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            日誌: {{ $entry->date->format('Y/m/d') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-lg">
                        総カロリー:
                        <span class="font-semibold">{{ number_format($entry->totalCalories(), 1) }} kcal</span>
                    </p>
                </div>
            </div>

            {{-- Food items --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">食べた食品</h3>
                    <ul class="divide-y divide-gray-100 mb-6">
                        @forelse ($entry->foodItems as $item)
                            <li class="py-2 flex items-center justify-between">
                                <span>
                                    {{ $item->food->name }}（{{ $item->grams }} g）
                                    <span class="text-sm text-gray-500">{{ number_format($item->calories(), 1) }} kcal</span>
                                </span>
                                <form method="POST" action="{{ route('dashboard.diary-entries.food-items.destroy', [$entry, $item]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">削除</button>
                                </form>
                            </li>
                        @empty
                            <li class="py-2 text-gray-600">まだ食品が追加されていません。</li>
                        @endforelse
                    </ul>

                    <form method="POST" action="{{ route('dashboard.diary-entries.food-items.store', $entry) }}" class="flex flex-wrap gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="food_id" value="食品" />
                            <select id="food_id" name="food_id" class="mt-1 block border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($foods as $food)
                                    <option value="{{ $food->id }}">{{ $food->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="grams" value="量 (g)" />
                            <x-text-input id="grams" name="grams" type="number" step="0.1" min="0.1" class="mt-1 block" value="100" required />
                        </div>
                        <x-primary-button>食品を追加</x-primary-button>
                    </form>
                </div>
            </div>

            {{-- Recipe items --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">食べたレシピ</h3>
                    <ul class="divide-y divide-gray-100 mb-6">
                        @forelse ($entry->recipeItems as $item)
                            <li class="py-2 flex items-center justify-between">
                                <span>
                                    {{ $item->recipe->name }}
                                    <span class="text-sm text-gray-500">{{ number_format($item->recipe->totalCalories(), 1) }} kcal</span>
                                </span>
                                <form method="POST" action="{{ route('dashboard.diary-entries.recipe-items.destroy', [$entry, $item]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">削除</button>
                                </form>
                            </li>
                        @empty
                            <li class="py-2 text-gray-600">まだレシピが追加されていません。</li>
                        @endforelse
                    </ul>

                    <form method="POST" action="{{ route('dashboard.diary-entries.recipe-items.store', $entry) }}" class="flex flex-wrap gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="recipe_id" value="レシピ" />
                            <select id="recipe_id" name="recipe_id" class="mt-1 block border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($recipes as $recipe)
                                    <option value="{{ $recipe->id }}">{{ $recipe->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>レシピを追加</x-primary-button>
                    </form>
                </div>
            </div>

            {{-- Private image --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">リマインダー画像（プライベート）</h3>
                    @if ($entry->image_path)
                        <img src="{{ route('dashboard.diary-entries.image.show', $entry) }}" alt="日誌の画像"
                             class="max-w-sm rounded-md border border-gray-200 mb-4">
                        <p class="text-xs text-gray-500 mb-4">この画像は非公開ストレージに保存され、あなただけが閲覧できます。</p>
                    @endif
                    <form method="POST" action="{{ route('dashboard.diary-entries.image.update', $entry) }}" enctype="multipart/form-data" class="flex flex-wrap gap-4 items-end">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-input-label for="image" value="画像ファイル" />
                            <input id="image" name="image" type="file" accept="image/*" class="mt-1 block" required>
                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                        </div>
                        <x-primary-button>アップロード</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard.diary-entries.index') }}" class="text-sm text-gray-600 hover:text-gray-900">一覧に戻る</a>
                <form method="POST" action="{{ route('dashboard.diary-entries.destroy', $entry) }}"
                      onsubmit="return confirm('この日誌を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>日誌を削除</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
