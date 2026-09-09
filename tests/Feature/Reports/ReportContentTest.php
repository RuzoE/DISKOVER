<?php

namespace Tests\Feature\Reports;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use App\Services\Reports\CourseAcademicReport;
use App\Services\Reports\InstitutionalReport;
use App\Services\Reports\StudentTranscriptReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ReportContentTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_course_academic_report_flags_pass_and_fail(): void
    {
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create();
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);

        $passing = User::factory()->withRole('student')->create(['name' => 'Aprueba']);
        $failing = User::factory()->withRole('student')->create(['name' => 'Suspende']);
        foreach ([$passing, $failing] as $student) {
            Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
        }
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $passing->id, 'score' => 85]);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $failing->id, 'score' => 30]);

        $rows = collect(app(CourseAcademicReport::class, ['course' => $course])->rows())->keyBy(0);

        $this->assertSame('Aprobado', $rows->get('Aprueba')[7]);
        $this->assertSame('No superado', $rows->get('Suspende')[7]);
    }

    public function test_transcript_lists_one_row_per_subject_with_overall_average(): void
    {
        $student = User::factory()->withRole('student')->create();
        $course = Course::factory()->create();
        $s1 = Subject::factory()->for($course)->create();
        $s2 = Subject::factory()->for($course)->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $a1 = Activity::factory()->for($s1)->create(['max_score' => 100]);
        $a2 = Activity::factory()->for($s2)->create(['max_score' => 100]);
        Grade::factory()->create(['activity_id' => $a1->id, 'student_id' => $student->id, 'score' => 80]);
        Grade::factory()->create(['activity_id' => $a2->id, 'student_id' => $student->id, 'score' => 60]);

        $report = app(StudentTranscriptReport::class, ['student' => $student]);

        $this->assertCount(2, $report->rows());
        $this->assertSame('70 %', $report->summary()['Promedio global']); // (80 + 60) / 2
    }

    public function test_institutional_report_counts_reflect_the_data(): void
    {
        Course::factory()->count(3)->create();
        Course::factory()->create(['status' => 'archived']);

        $summary = app(InstitutionalReport::class)->summary();

        $this->assertSame('4 (3)', $summary['Cursos (activos)']);
    }
}
