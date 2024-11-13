<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(): View|Factory|Application
    {
        $authId = auth()->id();
        $users = User::query()->where('id', '!=', $authId)->get();
        return view('chat', ['users' => $users]);
    }

    public function show($userId): View|Factory|Application
    {
        $authId = auth()->id();

        $users = User::query()->where('id', '!=', $authId)->get();
        $selectedUser = User::query()->findOrFail($userId);

        $messages = Message::query()->where(function ($query) use ($authId, $userId) {
            $query->where('sender_id', $authId)
                ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($authId, $userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $authId);
        })->orderBy('created_at')->get();

        return view('chat', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'messages' => $messages
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        $message = Message::query()->create([
            'sender_id' => auth()->id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        return response()->json(['message' => $message]);
    }

    public function getMessages($userId): JsonResponse
    {
        $authId = auth()->id();

        $messages = Message::query()->where(function ($query) use ($authId, $userId) {
            $query->where('sender_id', $authId)
                ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($authId, $userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $authId);
        })->orderBy('created_at')->get();

        return response()->json(['messages' => $messages]);
    }
}
