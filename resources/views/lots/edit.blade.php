@extends('layouts.app')

@section('title', "Редактировать лот #{$lot->id} — SportPay")

@section('content')
<div class="max-w-2xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-extrabold text-white">Редактирование лота #{{ $lot->id }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">Измените параметры предложения или снимите его с продажи</p>
        </div>
        <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-white">← Назад на витрину</a>
    </div>

    <form action="{{ route('lots.update', $lot) }}" method="POST" class="space-y-4 text-sm">
        @csrf
        @method('PUT')

        <div>
            <label class="text-xs text-slate-400 font-semibold">Название лота</label>
            <input type="text" name="title" value="{{ old('title', $lot->title) }}" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-slate-400 font-semibold">Категория</label>
                <select name="product_type" class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-white">
                    <option value="training_plan" @selected($lot->product_type === 'training_plan')>Программа</option>
                    <option value="coaching" @selected($lot->product_type === 'coaching')>Тренерство</option>
                    <option value="race_slot" @selected($lot->product_type === 'race_slot')>Слот на забег</option>
                    <option value="equipment" @selected($lot->product_type === 'equipment')>Экипировка</option>
                </select>
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold">Цена (₽)</label>
                <input type="number" name="price" value="{{ old('price', (float) $lot->price) }}" min="50" step="10" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold">Остаток (шт.)</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $lot->stock_quantity) }}" min="0" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">
            </div>
        </div>

        <div>
            <label class="text-xs text-slate-400 font-semibold">Подробное описание</label>
            <textarea name="description" rows="4" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">{{ old('description', $lot->description) }}</textarea>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                Сохранить изменения
            </button>
        </div>
    </form>

    <!-- Отдельная форма удаления лота -->
    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs text-slate-400">Хотите убрать лот с витрины?</span>
        <form action="{{ route('lots.destroy', $lot) }}" method="POST" onsubmit="return confirm('Удалить этот лот?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-300 font-bold text-xs">
                Удалить лот
            </button>
        </form>
    </div>
</div>
@endsection