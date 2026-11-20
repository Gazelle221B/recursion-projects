<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">日誌を作成</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.diary-entries.store') }}" class="space-y-6">
                        @csrf
                        <div>
                            <x-input-label for="date" value="日付" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full"
                                          :value="old('date', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">日時は UTC で保存されます。</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <x-primary-button>作成する</x-primary-button>
                            <a href="{{ route('dashboard.diary-entries.index') }}" class="text-sm text-gray-600 hover:text-gray-900">キャンセル</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
