{{-- Shared recipe form. Expects: $recipe, $foods, $action, $method --}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="レシピ名" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $recipe->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="creation_date" value="作成日" />
        <x-text-input id="creation_date" name="creation_date" type="date" class="mt-1 block w-full"
                      :value="old('creation_date', $recipe->creation_date?->toDateString() ?? now()->toDateString())" />
        <x-input-error :messages="$errors->get('creation_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label value="食材（1つ以上選択）" />
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @foreach ($foods as $food)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="food_ids[]" value="{{ $food->id }}"
                           class="rounded border-gray-300"
                           @checked(in_array($food->id, old('food_ids', $recipe->foods->pluck('id')->all())))>
                    {{ $food->name }}
                    <span class="text-gray-500">（{{ number_format($food->getTotalCalories(), 1) }} kcal）</span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('food_ids')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>保存する</x-primary-button>
        <a href="{{ route('dashboard.recipes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">キャンセル</a>
    </div>
</form>
