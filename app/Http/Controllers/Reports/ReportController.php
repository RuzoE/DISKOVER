<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Services\Reports\Contracts\Report;
use App\Services\Reports\CourseAcademicReport;
use App\Services\Reports\InstitutionalReport;
use App\Services\Reports\ReportExporter;
use App\Services\Reports\StudentTranscriptReport;
use App\Services\Reports\SubjectPerformanceReport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportes formales y exportables. Cada acción construye el Report adecuado y lo
 * renderiza o lo descarga en CSV (`?export=csv`). La autorización reutiliza las
 * policies de curso/asignatura ya existentes.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportExporter $exporter) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('reports.index', [
            'canInstitutional' => $user->hasAnyRole(['admin', 'coordinator']),
            'canCourses' => $user->hasAnyRole(['admin', 'coordinator']),
            'canSubjects' => $user->hasAnyRole(['admin', 'coordinator', 'teacher']),
            'courses' => $user->hasAnyRole(['admin', 'coordinator'])
                ? Course::orderBy('code')->get(['id', 'code', 'name'])
                : collect(),
            'subjects' => $user->hasRole('teacher') && ! $user->isAdmin()
                ? $user->subjectsTeaching()->with('course')->orderBy('name')->get()
                : ($user->hasAnyRole(['admin', 'coordinator'])
                    ? Subject::with('course')->orderBy('name')->get()
                    : collect()),
        ]);
    }

    public function institutional(Request $request): View|Response
    {
        return $this->deliver($request, app(InstitutionalReport::class));
    }

    public function course(Request $request, Course $course): View|Response
    {
        $this->authorize('view', $course);

        return $this->deliver($request, app(CourseAcademicReport::class, ['course' => $course]));
    }

    public function subject(Request $request, Subject $subject): View|Response
    {
        $this->authorize('view', $subject);

        return $this->deliver($request, app(SubjectPerformanceReport::class, ['subject' => $subject->load('course', 'teacher')]));
    }

    public function transcript(Request $request, User $user): View|Response
    {
        return $this->deliver($request, app(StudentTranscriptReport::class, ['student' => $user]));
    }

    public function myTranscript(Request $request): View|Response
    {
        return $this->deliver($request, app(StudentTranscriptReport::class, ['student' => $request->user()]));
    }

    private function deliver(Request $request, Report $report): View|Response
    {
        if ($request->query('export') === 'csv') {
            return $this->exporter->csv($report);
        }

        return view('reports.show', [
            'report' => $report,
            'exportUrl' => $request->fullUrlWithQuery(['export' => 'csv']),
        ]);
    }
}
