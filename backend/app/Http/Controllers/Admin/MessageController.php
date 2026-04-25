<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $adminId = $request->user()->id;
        
        $query = Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($adminId) {
                $q->where('sender_id', $adminId)
                  ->orWhere('receiver_id', $adminId);
            })
            ->latest();

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('tag')) {
            $query->where('tag', $request->tag);
        }

        $messages = $query->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'tag' => 'required|string|in:item_requirement,customer_inquiry,staff_duty,incident,other',
            'priority' => 'required|string|in:high,medium,low',
        ]);

        $message = Message::create([
            'tenant_id' => $request->user()->tenant_id,
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'title' => $request->title,
            'content' => $request->content,
            'tag' => $request->tag,
            'priority' => $request->priority,
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load(['sender', 'receiver'])
        ], 201);
    }

    public function markAsRead(Request $request, Message $message): JsonResponse
    {
        // Only the receiver can mark it as read
        if ($message->receiver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->update(['is_read' => true]);

        return response()->json([
            'message' => 'Message marked as read',
            'data' => $message
        ]);
    }
}
