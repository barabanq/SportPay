@extends('layouts.app')

@section('title', "Заказ #{$order->id} — SportPay")

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Левая колонка: Информация о сделке и управление Эскроу -->
    <div class="space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-400 font-bold uppercase">Сделка #{{ $order->id }}</span>
                <span class="px-2.5 py-1 rounded text-xs font-bold {{ $order->status->badgeClasses() }}">
                    {{ $order->status->label() }}
                </span>
            </div>

            <div>
                <h1 class="text-lg font-extrabold text-white">{{ $order->lot->title ?? 'Лот' }}</h1>
                <p class="text-xs text-slate-400 mt-1">{{ $order->lot->description ?? '' }}</p>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-400">Продавец:</span>
                    <a href="{{ route('users.show', $order->seller_id) }}" class="font-bold text-emerald-400 hover:underline">
                        {{ $order->seller->username }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Покупатель:</span>
                    <a href="{{ route('users.show', $order->buyer_id) }}" class="font-bold text-indigo-400 hover:underline">
                        {{ $order->buyer->username }}
                    </a>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-800">
                    <span class="text-slate-400">Сумма в гаранте:</span>
                    <span class="font-extrabold text-emerald-400">{{ number_format((float) $order->total_price, 2, '.', ' ') }} ₽</span>
                </div>
            </div>

            @if($order->status->isConfirmable())
                @if($user->id === $order->buyer_id)
                    <form action="{{ route('orders.confirm', $order) }}" method="POST" class="space-y-3 pt-2 border-t border-slate-800">
                        @csrf
                        <div class="text-xs text-slate-300 font-semibold">Проверили товар? Подтвердите сделку:</div>
                        <select name="rating" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-amber-400 font-bold">
                            <option value="5">★★★★★ Отлично (5)</option>
                            <option value="4">★★★★☆ Хорошо (4)</option>
                            <option value="3">★★★☆☆ Нормально (3)</option>
                        </select>
                        <textarea name="comment" rows="2" placeholder="Напишите отзыв продавцу..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white"></textarea>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                            Подтвердить выполнение и отправить деньги
                        </button>
                    </form>
                @else
                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-300">
                        Вы продавец. Передайте товар или проведите консультацию в чате справа, после чего покупатель подтвердит заказ.
                    </div>
                @endif

                <form action="{{ route('orders.cancel', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-300 font-bold text-xs">
                        Отменить сделку (Возврат средств)
                    </button>
                </form>
            @elseif($order->review)
                <div class="p-3.5 rounded-xl bg-slate-800/70 border border-slate-700 text-xs space-y-1">
                    <div class="text-amber-400 font-bold">Отзыв по сделке: {{ str_repeat('★', $order->review->rating) }}</div>
                    <div class="text-slate-200">«{{ $order->review->comment }}»</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Правая колонка (2/3): Чат сделки между Покупателем и Продавцом -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between min-h-[520px]">
        <div>
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800">
                <h2 class="text-base font-bold text-white">Чат по сделке #{{ $order->id }}</h2>
                <span class="text-xs text-slate-400">Гарант безопасности SportPay</span>
            </div>

            <div class="space-y-3 max-h-[360px] overflow-y-auto pr-2">
                @forelse($order->messages as $msg)
                    @if($msg->is_system)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300">
                            🛡️ {{ $msg->body }}
                        </div>
                    @else
                        @php $isMe = $msg->user_id === $user->id; @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                            <div class="text-[11px] text-slate-400 mb-1">
                                <a href="{{ route('users.show', $msg->user_id) }}" class="font-bold hover:underline">{{ $msg->user->username ?? 'Юзер' }}</a>
                                • {{ $msg->created_at?->format('H:i') }}
                            </div>
                            <div class="max-w-md px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">
                                {{ $msg->body }}
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center text-sm text-slate-500 py-12">
                        Сообщений пока нет. Напишите продавцу или покупателю первым!
                    </div>
                @endforelse
            </div>
        </div>

        <form action="{{ route('orders.messages.store', $order) }}" method="POST" class="mt-4 pt-3 border-t border-slate-800 flex gap-2">
            @csrf
            <input type="text" name="body" required placeholder="Написать сообщение в чат сделки..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                Отправить
            </button>
        </form>
    </div>
</div>
@endsection