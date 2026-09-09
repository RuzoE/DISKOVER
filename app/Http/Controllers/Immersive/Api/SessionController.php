<?php

namespace App\Http\Controllers\Immersive\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Immersive\CompleteSessionRequest;
use App\Models\ImmersiveSession;
use App\Services\Immersive\ImmersiveSessionService;
use Illuminate\Http\JsonResponse;

/**
 * API que consume el cliente inmersivo (Unity). Se autentica por el
 * `launch_token` de la sesión: cada token identifica a un estudiante y una
 * experiencia. Ver ADR-0012.
 */
class SessionController extends Controller
{
    public function __construct(private readonly ImmersiveSessionService $sessions) {}

    public function show(string $token): JsonResponse
    {
        $session = $this->resolve($token);
        $experience = $session->experience;

        return response()->json([
            'session' => [
                'token' => $session->launch_token,
                'status' => $session->status->value,
                'started_at' => $session->started_at?->toIso8601String(),
                'expired' => $session->isExpired(),
            ],
            'experience' => [
                'slug' => $experience->slug,
                'title' => $experience->title,
                'provider' => $experience->provider->value,
                'launch_url' => $experience->launch_url,
                'max_score' => (float) $experience->max_score,
                'config' => $experience->config ?? new \stdClass,
            ],
            'student' => [
                'id' => $session->student_id,
                'name' => $session->student->name,
            ],
        ]);
    }

    public function complete(CompleteSessionRequest $request, string $token): JsonResponse
    {
        $session = $this->sessions->complete(
            $this->resolve($token),
            $request->float('score'),
            (array) $request->input('payload', []),
        );

        return response()->json([
            'status' => $session->status->value,
            'score' => (float) $session->score,
            'max_score' => (float) $session->max_score,
            'percentage' => $session->percentage(),
        ]);
    }

    public function abandon(string $token): JsonResponse
    {
        $session = $this->sessions->abandon($this->resolve($token));

        return response()->json(['status' => $session->status->value]);
    }

    private function resolve(string $token): ImmersiveSession
    {
        return ImmersiveSession::query()
            ->with(['experience.activity', 'student'])
            ->where('launch_token', $token)
            ->firstOrFail();
    }
}
