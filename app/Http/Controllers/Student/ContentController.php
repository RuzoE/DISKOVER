<?php

namespace App\Http\Controllers\Student;

use App\Events\Academic\ContentCompleted;
use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * Marca o desmarca un contenido como completado por el estudiante.
     */
    public function complete(Request $request, Content $content): RedirectResponse
    {
        $student = $request->user();
        $content->loadMissing('subject.course');

        abort_unless($student->isEnrolledIn($content->subject->course), 403);
        abort_unless($content->is_published, 404);

        if ($content->isCompletedBy($student)) {
            $student->completedContents()->detach($content->id);
            $message = 'Contenido marcado como pendiente.';
        } else {
            $student->completedContents()->attach($content->id, ['completed_at' => now()]);
            ContentCompleted::dispatch($content, $student);
            $message = 'Contenido marcado como completado.';
        }

        return back()->with('status', $message);
    }
}
