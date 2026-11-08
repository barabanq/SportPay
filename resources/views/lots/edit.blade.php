@extends('layouts.app')

@section('title', __('Редактирование лота') . " #{$lot->id} — SportPay")

@section('content')
<!-- Подключаем стили и скрипты Leaflet карты -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="max-w-2xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-6">
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-white">{{ __('Редактирование лота') }} #{{ $lot->id }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ __('Измените параметры предложения или снимите его с продажи') }}</p>
        </div>
        <a href="{{ route('lots.show', $lot) }}" class="text-xs text-slate-400 hover:text-white">{{ __('← К карточке лота') }}</a>
    </div>

    <form action="{{ route('lots.update', $lot) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-sm">
        @csrf
        @method('PUT')

        <div>
            <label class="text-xs text-slate-400 font-semibold">{{ __('Название лота') }}</label>
            <input type="text" name="title" value="{{ old('title', $lot->title) }}" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-slate-400 font-semibold">{{ __('Категория') }}</label>
                <select name="product_type" class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-white">
                    <option value="training_plan" @selected($lot->product_type === 'training_plan')>{{ __('Программа') }}</option>
                    <option value="coaching" @selected($lot->product_type === 'coaching')>{{ __('Тренерство') }}</option>
                    <option value="race_slot" @selected($lot->product_type === 'race_slot')>{{ __('Слот на забег') }}</option>
                    <option value="equipment" @selected($lot->product_type === 'equipment')>{{ __('Экипировка') }}</option>
                </select>
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold">{{ __('Цена (₽)') }}</label>
                <input type="number" name="price" value="{{ old('price', (float) $lot->price) }}" min="50" step="10" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold">{{ __('Остаток (шт.)') }}</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $lot->stock_quantity) }}" min="0" required class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">
            </div>
        </div>

        <!-- Геолокация лота с интерактивной картой -->
        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <label class="text-xs text-emerald-400 font-semibold">📍 {{ __('Геопозиция лота (кликните по карте или введите цифры)') }}</label>
                <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <button type="button" onclick="setEditGeo('Москва, Лужники', 55.715765, 37.551521)" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300">Москва</button>
                    <button type="button" onclick="setEditGeo('Н. Новгород, Мещера', 56.339900, 43.933200)" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300">Н. Новгород</button>
                    <button type="button" onclick="detectEditGeo()" class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Мой GPS</button>
                </div>
            </div>

            <!-- Интерактивная карта Leaflet -->
            <div id="edit-lot-map" class="w-full h-56 rounded-xl border border-slate-700 z-10"></div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <input type="text" id="edit_location_name" name="location_name" value="{{ old('location_name', $lot->location_name) }}" placeholder="{{ __('Город, зал или стадион') }}" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                <input type="number" step="any" id="edit_latitude" name="latitude" value="{{ old('latitude', $lot->latitude) }}" placeholder="{{ __('Широта (55.7558)') }}" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                <input type="number" step="any" id="edit_longitude" name="longitude" value="{{ old('longitude', $lot->longitude) }}" placeholder="{{ __('Долгота (37.6173)') }}" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-xs text-slate-400 font-semibold">📷 {{ __('Фото лота') }}</label>
            @if($lot->imageUrl())
                <div class="flex items-center gap-4 p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <img src="{{ $lot->imageUrl() }}" alt="{{ $lot->title }}" class="w-20 h-20 rounded-xl object-cover border border-slate-700">
                    <label class="inline-flex items-center gap-2 text-xs text-rose-300 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" class="rounded bg-slate-900 border-slate-700 text-rose-500">
                        <span>{{ __('Удалить текущее фото') }}</span>
                    </label>
                </div>
            @endif
            <input type="file" name="image" accept="image/*" class="w-full text-xs text-slate-300 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-emerald-400 hover:file:bg-slate-700 cursor-pointer">
        </div>

        <div>
            <label class="text-xs text-slate-400 font-semibold">{{ __('Подробное описание (необязательно)') }}</label>
            <textarea name="description" rows="4" class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white">{{ old('description', $lot->description) }}</textarea>
        </div>

        <div>
            <label class="text-xs text-emerald-400 font-semibold">{{ __('⚡ Текст для мгновенной автовыдачи (необязательно)') }}</label>
            <textarea name="auto_delivery_text" rows="2" class="w-full mt-1 bg-slate-950 border border-emerald-500/40 rounded-xl px-4 py-2.5 text-xs text-emerald-300 placeholder-slate-500">{{ old('auto_delivery_text', $lot->auto_delivery_text) }}</textarea>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-slate-800">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                {{ __('Сохранить изменения') }}
            </button>
        </div>
    </form>

    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs text-slate-400">{{ __('Хотите убрать лот с витрины?') }}</span>
        <form action="{{ route('lots.destroy', $lot) }}" method="POST" onsubmit="return confirm('Delete this lot?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-300 font-bold text-xs">
                {{ __('✕ Удалить лот') }}
            </button>
        </form>
    </div>
</div>

<script>
let editMap = null;
let editMarker = null;
let editGeoTimer = null;

async function updateEditAddress(lat, lng) {
    const input = document.getElementById('edit_location_name');
    if (!input) return;
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=16&accept-language=ru`);
        if (!res.ok) return;
        const data = await res.json();
        if (data && data.address) {
            const city = data.address.city || data.address.town || data.address.village || data.address.state || '';
            const place = data.address.stadium || data.address.road || data.address.suburb || '';
            const label = [city, place].filter(Boolean).join(', ');
            if (label) input.value = label;
        }
    } catch (e) {}
}

function placeEditMarker(lat, lng, customName = null) {
    if (!editMap || typeof L === 'undefined') return;
    const latNum = parseFloat(lat);
    const lngNum = parseFloat(lng);
    if (isNaN(latNum) || isNaN(lngNum)) return;

    if (!editMarker) {
        editMarker = L.marker([latNum, lngNum], { draggable: true }).addTo(editMap);
        editMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            document.getElementById('edit_latitude').value = pos.lat.toFixed(6);
            document.getElementById('edit_longitude').value = pos.lng.toFixed(6);
            updateEditAddress(pos.lat, pos.lng);
        });
    } else {
        editMarker.setLatLng([latNum, lngNum]);
    }
    editMap.setView([latNum, lngNum], 13);

    if (customName !== null) {
        document.getElementById('edit_location_name').value = customName;
    } else {
        clearTimeout(editGeoTimer);
        editGeoTimer = setTimeout(() => updateEditAddress(latNum, lngNum), 400);
    }
}

function setEditGeo(name, lat, lng) {
    document.getElementById('edit_latitude').value = lat;
    document.getElementById('edit_longitude').value = lng;
    placeEditMarker(lat, lng, name);
}

function detectEditGeo() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude.toFixed(6);
        const lng = pos.coords.longitude.toFixed(6);
        document.getElementById('edit_latitude').value = lat;
        document.getElementById('edit_longitude').value = lng;
        placeEditMarker(lat, lng, null);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('edit-lot-map');
    if (container && typeof L !== 'undefined') {
        const initLat = parseFloat(document.getElementById('edit_latitude').value) || 55.7558;
        const initLng = parseFloat(document.getElementById('edit_longitude').value) || 37.6173;
        const hasExisting = Boolean(document.getElementById('edit_latitude').value && document.getElementById('edit_longitude').value);

        editMap = L.map('edit-lot-map').setView([initLat, initLng], hasExisting ? 13 : 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(editMap);

        if (hasExisting) {
            placeEditMarker(initLat, initLng, document.getElementById('edit_location_name').value);
        }

        editMap.on('click', function (e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            document.getElementById('edit_latitude').value = lat;
            document.getElementById('edit_longitude').value = lng;
            placeEditMarker(lat, lng, null);
        });
    }

    // Слушаем событие input — теперь цифры обновляют карту и адрес мгновенно при наборе
    ['edit_latitude', 'edit_longitude'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', function () {
            const lat = document.getElementById('edit_latitude').value;
            const lng = document.getElementById('edit_longitude').value;
            if (lat && lng) {
                placeEditMarker(lat, lng, null);
            }
        });
    });
});
</script>
@endsection