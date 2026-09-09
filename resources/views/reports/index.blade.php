@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Reportes</h1>
        <p class="page-header__subtitle">Reportes formales del sistema. Todos se pueden exportar a CSV.</p>
    </div>

    @if ($canInstitutional)
        <x-ui.card title="Indicadores institucionales">
            <p class="text-muted u-mb-4">KPIs de toda la plataforma y resumen por curso activo.</p>
            <x-ui.button :href="route('reports.institutional')" variant="primary">Abrir</x-ui.button>
        </x-ui.card>
    @endif

    @if ($canCourses && $courses->isNotEmpty())
        <x-ui.card title="Reporte académico por curso">
            <form method="GET" action="#" class="filters" onsubmit="event.preventDefault(); const c=this.course.value; if(c) window.location='{{ url('reports/courses') }}/'+c;">
                <select name="course" class="field__control" aria-label="Curso" required>
                    <option value="">Selecciona un curso…</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }}</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" variant="primary">Abrir</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    @if ($canSubjects && $subjects->isNotEmpty())
        <x-ui.card title="Reporte de desempeño por asignatura">
            <form method="GET" action="#" class="filters" onsubmit="event.preventDefault(); const s=this.subject.value; if(s) window.location='{{ url('reports/subjects') }}/'+s;">
                <select name="subject" class="field__control" aria-label="Asignatura" required>
                    <option value="">Selecciona una asignatura…</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->course->name }})</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" variant="primary">Abrir</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    @if ($canCourses)
        <x-ui.card title="Expediente de un estudiante">
            <p class="text-muted">Desde <a href="{{ route('admin.users.index') }}" class="link">Usuarios</a> o la ficha de cada estudiante.</p>
        </x-ui.card>
    @endif
@endsection
