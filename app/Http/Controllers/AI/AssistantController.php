<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\QuickMessageRequest;
use App\Http\Requests\AI\SendMessageRequest;
use App\Http\Requests\AI\StoreConversationRequest;
use App\Models\AiConversation;
use App\Services\AI\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

/**
 * Asistente educativo. Toda la lógica de IA vive en App\Services\AI\AiService;
 * el controlador sólo orquesta.
 */
class AssistantController extends Controller
{
    public function __construct(private readonly AiService $ai) {}

    public function index(Request $request): View
    {
        $conversations = $request->user()
            ->aiConversations()
            ->withCount('messages')
            ->paginate(20);

        return view('ai.index', [
            'conversations' => $conversations,
            'realProvider' => $this->ai->isRealProvider(),
        ]);
    }

    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $conversation = $this->ai->startConversation(
            $request->user(),
            $request->string('message')->value(),
            $request->input('context_type', 'general'),
            $request->integer('context_id') ?: null,
        );

        return redirect()->route('assistant.show', $conversation);
    }

    public function show(AiConversation $conversation): View
    {
        $this->authorize('view', $conversation);

        return view('ai.show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->where('role', '!=', 'system')->get(),
            'realProvider' => $this->ai->isRealProvider(),
        ]);
    }

    public function message(SendMessageRequest $request, AiConversation $conversation): RedirectResponse
    {
        $this->ai->sendMessage($conversation, $request->user(), $request->string('message')->value());

        return redirect()->route('assistant.show', $conversation);
    }

    /**
     * Asistente flotante: responde en JSON sin recargar la página.
     */
    public function quick(QuickMessageRequest $request): JsonResponse
    {
        $reply = $this->ai->quickMessage($request->user(), $request->string('message')->value());

        return response()->json([
            'reply' => $reply->content,
            'html' => Blade::render('<x-ai.bubble :message="$message" />', ['message' => $reply]),
            'failed' => $reply->failed,
            'integrity' => $reply->integrity,
        ]);
    }

    public function destroy(AiConversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $this->ai->deleteConversation($conversation);

        return redirect()->route('assistant.index')->with('status', 'Conversación eliminada.');
    }
}
