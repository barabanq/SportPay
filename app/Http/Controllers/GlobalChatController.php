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
            ->latest()
            ->limit(50)
            ->get()
            ->reverse();

        return view('chat.global', [
            'user' => $this->currentUser(),
            'allUsers' => User::orderBy('id')->get(),
            'messages' => $messages,
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

        // Отправляем событие в WebSocket-канал Reverb
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'created_at' => $message->created_at->format('H:i'),
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ],
            ],
        ]);
    }
}