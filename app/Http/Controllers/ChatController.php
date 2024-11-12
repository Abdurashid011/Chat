<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(): View|Factory|Application
    {
        $authId = auth()->id();
        $users = User::query()->where('id', '!=', $authId)->get();
        return view('chat', compact('users'));
    }

    public function show($userId): View|Factory|Application
    {
        $authId = auth()->id();

        $users = User::query()->where('id', '!=', $authId)->get();

        $selectedUser = User::query()->findOrFail($userId);

        $messages = Message::query()->where(function($query) use ($authId, $userId) {
            $query->where('sender_id', $authId)
                ->where('receiver_id', $userId);
        })->orWhere(function($query) use ($authId, $userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $authId);
        })->orderBy('created_at')->get();

        return view('chat', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'messages' => $messages
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $authId = auth()->id();

        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        Message::query()->create([
            'sender_id' => $authId,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        return redirect()->route('chat.show', $validated['receiver_id']);
    }
}
