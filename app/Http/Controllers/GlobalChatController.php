<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\GlobalMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalChatController extends Controller
{
    public function index(): View
    {
        $messages = GlobalMessage::with('user')
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return view('chat.global', [
            'user' => $this->currentUser(),
            'allUsers' => User::orderBy('id')->get(),
            'messages' => $messages,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $afterId = (int) $request->query('after_id', 0);

        $messages = GlobalMessage::with('user')
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'messages' => $messages->map(fn (GlobalMessage $msg): array => [
                'id' => $msg->id,
                'user_id' => $msg->user_id,
                'username' => $msg->user->username ?? 'Юзер',
                'body' => $msg->body,
                'time' => $msg->created_at?->format('H:i') ?? now()->format('H:i'),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $message = GlobalMessage::create([
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        $message->load('user');

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Игнорируем ошибку сокета, если Reverb не запущен
        }

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'user_id' => $user->id,
                'username' => $user->username,
                'body' => $message->body,
                'time' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
            ],
        ]);
    }
}
