<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recommendations\RespondRecommendationRequest;
use App\Models\Recommendation;
use App\Services\Recommendations\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendations) {}

    public function index(Request $request): View
    {
        $student = $request->user();

        $this->recommendations->generateFor($student);

        return view('student.recommendations.index', [
            'open' => $this->recommendations->openFor($student),
            'resolved' => $this->recommendations->resolvedFor($student),
            'stats' => $this->recommendations->stats($student),
        ]);
    }

    public function respond(RespondRecommendationRequest $request, Recommendation $recommendation): RedirectResponse
    {
        $this->recommendations->respond($recommendation, $request->targetStatus());

        return redirect()
            ->route('student.recommendations.index')
            ->with('status', 'Recomendación actualizada.');
    }
}
