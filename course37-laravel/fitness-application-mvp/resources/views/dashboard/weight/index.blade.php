<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">体重の記録</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">体重を記録する</h3>
                    <form method="POST" action="{{ route('dashboard.weight.store') }}" class="flex flex-wrap gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="weight" value="体重 (kg)" />
                            <x-text-input id="weight" name="weight" type="number" step="0.1" min="0.1" class="mt-1 block"
                                          :value="old('weight')" required />
                        </div>
                        <div>
                            <x-input-label for="recorded_on" value="日付" />
                            <x-text-input id="recorded_on" name="recorded_on" type="date" class="mt-1 block"
                                          :value="old('recorded_on', now()->toDateString())" required />
                        </div>
                        <x-primary-button>記録する</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <li class="p-4 flex items-center justify-between">
                            <span>
                                <span class="font-medium text-gray-800">{{ $entry->recorded_on->format('Y/m/d') }}</span>
                                <span class="text-gray-600">{{ $entry->weight }} kg</span>
                            </span>
                            <form method="POST" action="{{ route('dashboard.weight.destroy', $entry) }}"
                                  onsubmit="return confirm('この記録を削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">削除</button>
                            </form>
                        </li>
                    @empty
                        <li class="p-4 text-gray-600">体重の記録がありません。</li>
                    @endforelse
                </ul>
            </div>

            <div>
                {{ $entries->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
