{{-- Shared food form. Expects: $food, $foodTypes, $foodTags, $action, $method --}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="名前" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $food->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="protein" value="たんぱく質 (g)" />
            <x-text-input id="protein" name="protein" type="number" step="0.1" min="0" class="mt-1 block w-full"
                          :value="old('protein', $food->protein)" required />
            <x-input-error :messages="$errors->get('protein')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="carbs" value="炭水化物 (g)" />
            <x-text-input id="carbs" name="carbs" type="number" step="0.1" min="0" class="mt-1 block w-full"
                          :value="old('carbs', $food->carbs)" required />
            <x-input-error :messages="$errors->get('carbs')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="fat" value="脂質 (g)" />
            <x-text-input id="fat" name="fat" type="number" step="0.1" min="0" class="mt-1 block w-full"
                          :value="old('fat', $food->fat)" required />
            <x-input-error :messages="$errors->get('fat')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="grams" value="基準量 (g)" />
            <x-text-input id="grams" name="grams" type="number" step="0.1" min="0.1" class="mt-1 block w-full"
                          :value="old('grams', $food->grams ?? 100)" required />
            <x-input-error :messages="$errors->get('grams')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">栄養素の値は、この基準量あたりの量です。</p>
        </div>
    </div>

    <div>
        <x-input-label for="food_type_name" value="食品の種類" />
        <select id="food_type_name" name="food_type_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">（未分類）</option>
            @foreach ($foodTypes as $type)
                <option value="{{ $type->name }}" @selected(old('food_type_name', $food->food_type_name) === $type->name)>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('food_type_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label value="タグ" />
        <div class="mt-2 flex flex-wrap gap-4">
            @foreach ($foodTags as $tag)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                           class="rounded border-gray-300"
                           @checked(in_array($tag->id, old('tags', $food->tags->pluck('id')->all())))>
                    {{ $tag->name }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('tags')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>保存する</x-primary-button>
        <a href="{{ route('dashboard.foods.index') }}" class="text-sm text-gray-600 hover:text-gray-900">キャンセル</a>
    </div>
</form>
