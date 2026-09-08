<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreContentRequest;
use App\Http\Requests\Academic\UpdateContentRequest;
use App\Models\Content;
use App\Models\Subject;
use App\Services\Academic\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(private readonly ContentService $contents) {}

    public function index(Subject $subject): View
    {
        $this->authorize('view', $subject); // SubjectPolicy: titular o coordinación

        return view('teacher.contents.index', [
            'subject' => $subject->load('course'),
            'contents' => $subject->contents()->get(),
        ]);
    }

    public function create(Subject $subject): View
    {
        $this->authorize('create', [Content::class, $subject]);

        return view('teacher.contents.create', [
            'subject' => $subject->load('course'),
            'types' => ContentType::options(),
        ]);
    }

    public function store(StoreContentRequest $request, Subject $subject): RedirectResponse
    {
        $this->contents->create($subject, $request->user(), $request->validated());

        return redirect()
            ->route('teacher.subjects.contents.index', $subject)
            ->with('status', 'Contenido añadido.');
    }

    public function edit(Content $content): View
    {
        $this->authorize('update', $content);

        return view('teacher.contents.edit', [
            'content' => $content->load('subject.course'),
            'subject' => $content->subject,
            'types' => ContentType::options(),
        ]);
    }

    public function update(UpdateContentRequest $request, Content $content): RedirectResponse
    {
        $this->contents->update($content, $request->validated());

        return redirect()
            ->route('teacher.subjects.contents.index', $content->subject_id)
            ->with('status', 'Contenido actualizado.');
    }

    public function destroy(Content $content): RedirectResponse
    {
        $this->authorize('delete', $content);

        $subjectId = $content->subject_id;
        $this->contents->delete($content);

        return redirect()
            ->route('teacher.subjects.contents.index', $subjectId)
            ->with('status', 'Contenido eliminado.');
    }
}
