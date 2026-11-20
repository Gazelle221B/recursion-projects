<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">運動の記録</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">運動を記録する</h3>
                    <form method="POST" action="{{ route('dashboard.exercises.store') }}" class="space-y-4">
                        @csrf
                        <div class="flex flex-wrap gap-4 items-end">
                            <div>
                                <x-input-label for="exercise_type_id" value="運動の種類" />
                                <select id="exercise_type_id" name="exercise_type_id" class="mt-1 block border-gray-300 rounded-md shadow-sm" required>
                                    @foreach ($exerciseTypes as $type)
                                        <option value="{{ $type->id }}" @selected(old('exercise_type_id') == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="duration_minutes" value="時間（分）" />
                                <x-text-input id="duration_minutes" name="duration_minutes" type="number" min="1" class="mt-1 block"
                                              :value="old('duration_minutes', 30)" required />
                            </div>
                            <div>
                                <x-input-label for="performed_at" value="実施日時" />
                                <x-text-input id="performed_at" name="performed_at" type="datetime-local" class="mt-1 block"
                                              :value="old('performed_at', now()->format('Y-m-d\TH:i'))" required />
                            </div>
                            <x-primary-button>記録する</x-primary-button>
                        </div>
                        <div>
                            <x-input-label value="タグ" />
                            <div class="mt-2 flex flex-wrap gap-4">
                                @foreach ($exerciseTags as $tag)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                               class="rounded border-gray-300"
                                               @checked(in_array($tag->id, old('tags', [])))>
                                        {{ $tag->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-100">
                    @forelse ($exercises as $exercise)
                        <li class="p-4 flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-800">{{ $exercise->exerciseType->name }}</span>
                                <span class="text-sm text-gray-500">
                                    {{ $exercise->performed_at->format('Y/m/d H:i') }} ・ {{ $exercise->duration_minutes }} 分
                                </span>
                                @if ($exercise->tags->isNotEmpty())
                                    <span class="ms-2">
                                        @foreach ($exercise->tags as $tag)
                                            <span class="inline-block rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">{{ $tag->name }}</span>
                                        @endforeach
                                    </span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('dashboard.exercises.destroy', $exercise) }}"
                                  onsubmit="return confirm('この記録を削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">削除</button>
                            </form>
                        </li>
                    @empty
                        <li class="p-4 text-gray-600">運動の記録がありません。</li>
                    @endforelse
                </ul>
            </div>

            <div>
                {{ $exercises->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
