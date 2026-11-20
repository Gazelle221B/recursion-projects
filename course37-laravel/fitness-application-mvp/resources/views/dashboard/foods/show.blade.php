<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $food->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">食品の種類</dt>
                            <dd class="font-medium">{{ $food->food_type_name ?? '未分類' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">基準量</dt>
                            <dd class="font-medium">{{ $food->grams }} g</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">たんぱく質</dt>
                            <dd class="font-medium">{{ $food->protein }} g</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">炭水化物</dt>
                            <dd class="font-medium">{{ $food->carbs }} g</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">脂質</dt>
                            <dd class="font-medium">{{ $food->fat }} g</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">総カロリー</dt>
                            <dd class="font-medium">{{ number_format($food->getTotalCalories(), 1) }} kcal</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">作成者</dt>
                            <dd class="font-medium">{{ $food->owner?->name ?? 'システム' }}</dd>
                        </div>
                    </dl>

                    @if ($food->tags->isNotEmpty())
                        <p class="flex flex-wrap gap-1">
                            @foreach ($food->tags as $tag)
                                <span class="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800">{{ $tag->name }}</span>
                            @endforeach
                        </p>
                    @endif

                    <div class="flex items-center gap-4 pt-2">
                        <a href="{{ route('dashboard.foods.index') }}" class="text-sm text-gray-600 hover:text-gray-900">一覧に戻る</a>
                        @can('update', $food)
                            <a href="{{ route('dashboard.foods.edit', $food) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                編集
                            </a>
                        @endcan
                        @can('delete', $food)
                            <form method="POST" action="{{ route('dashboard.foods.destroy', $food) }}"
                                  onsubmit="return confirm('この食品を削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button>削除</x-danger-button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
