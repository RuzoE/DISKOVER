<?php

namespace Tests\Feature\Tracking;

use App\Models\Activity;
use App\Models\Content;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use App\Services\Analytics\ProgressCalculator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_subject_progress_counts_contents_and_activities(): void
    {
        $subject = Subject::factory()->create();
        $contents = Content::factory()->count(2)->for($subject)->create(['is_published' => true]);
        $activities = Activity::factory()->count(2)->for($subject)->create(['is_published' => true, 'max_score' => 10]);
        Content::factory()->for($subject)->unpublished()->create(); // no cuenta

        $student = User::factory()->create();
        $student->completedContents()->attach($contents->first()->id, ['completed_at' => now()]);
        Grade::factory()->create([
            'activity_id' => $activities->first()->id,
            'student_id' => $student->id,
            'score' => 8,
        ]);

        $progress = app(ProgressCalculator::class)->forSubject($student, $subject);

        $this->assertSame(4, $progress['items_total']);   // 2 contenidos + 2 actividades
        $this->assertSame(2, $progress['items_done']);    // 1 contenido + 1 actividad
        $this->assertSame(50.0, $progress['percentage']);
        $this->assertSame(80.0, $progress['average']);    // 8/10
        $this->assertSame(1, $progress['pending']);
    }

    public function test_overdue_activities_are_counted_separately(): void
    {
        $subject = Subject::factory()->create();
        Activity::factory()->for($subject)->create(['is_published' => true, 'due_at' => now()->subDay()]);
        Activity::factory()->for($subject)->create(['is_published' => true, 'due_at' => now()->addDay()]);

        $progress = app(ProgressCalculator::class)->forSubject(User::factory()->create(), $subject);

        $this->assertSame(1, $progress['overdue']);
        $this->assertSame(1, $progress['pending']);
    }

    public function test_course_progress_aggregates_its_active_subjects(): void
    {
        $course = Course::factory()->create();
        $s1 = Subject::factory()->for($course)->create();
        $s2 = Subject::factory()->for($course)->create();
        Content::factory()->for($s1)->create(['is_published' => true]);
        Content::factory()->for($s2)->create(['is_published' => true]);

        $student = User::factory()->create();
        $student->completedContents()->attach($s1->contents()->first()->id, ['completed_at' => now()]);

        $progress = app(ProgressCalculator::class)->forCourse($student, $course);

        $this->assertSame(2, $progress['items_total']);
        $this->assertSame(1, $progress['items_done']);
        $this->assertSame(50.0, $progress['percentage']);
        $this->assertCount(2, $progress['subjects']);
    }

    public function test_empty_subject_reports_zero_progress_not_division_error(): void
    {
        $subject = Subject::factory()->create();

        $progress = app(ProgressCalculator::class)->forSubject(User::factory()->create(), $subject);

        $this->assertSame(0.0, $progress['percentage']);
        $this->assertNull($progress['average']);
    }
}
