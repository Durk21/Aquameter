<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AiChatController extends Controller
{
    public function messages(Request $request): JsonResponse
    {
        $messages = ChatMessage::where("user_id", $request->user()->id)
            ->orderBy("created_at")
            ->limit(30)
            ->get()
            ->map(fn (ChatMessage $message) => $this->present($message));

        return response()->json(["messages" => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        // Validated manually (not via $request->validate()) so a failure
        // returns clean JSON — this app's exception handler only renders
        // JSON automatically for /api/* routes (bootstrap/app.php), and
        // this is a JSON endpoint living outside that prefix by design.
        $validator = Validator::make($request->all(), [
            "message" => "required|string|max:1000",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $reply = AiChatService::reply($request->user(), $validator->validated()["message"]);

        return response()->json([
            "reply" => $this->present($reply),
        ]);
    }

    protected function present(ChatMessage $message): array
    {
        return [
            "id" => $message->id,
            "role" => $message->role->value,
            "content" => $message->content,
            "created_at" => $message->created_at->toIso8601String(),
        ];
    }
}
