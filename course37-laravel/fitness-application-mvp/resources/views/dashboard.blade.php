<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    ようこそ、Fitness Application へ。体重やカロリー摂取を記録して、フィットネス目標の達成をサポートします。
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('dashboard.foods.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold text-gray-800">食品</h3>
                    <p class="mt-1 text-sm text-gray-600">食品の検索・作成・編集を行います。</p>
                </a>
                <a href="{{ route('dashboard.diary-entries.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold text-gray-800">日誌</h3>
                    <p class="mt-1 text-sm text-gray-600">毎日の食事と総カロリーを記録します。</p>
                </a>
                <a href="{{ route('dashboard.recipes.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold text-gray-800">レシピ</h3>
                    <p class="mt-1 text-sm text-gray-600">複数の食材を組み合わせたレシピを管理します。</p>
                </a>
                <a href="{{ route('dashboard.exercises.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold text-gray-800">運動</h3>
                    <p class="mt-1 text-sm text-gray-600">行った運動を種類とタグ付きで記録します。</p>
                </a>
                <a href="{{ route('dashboard.weight.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold text-gray-800">体重</h3>
                    <p class="mt-1 text-sm text-gray-600">体重の推移を記録します。</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
