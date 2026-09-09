<?php

namespace Tests\Unit;

use App\Enums\AcademicStatus;
use App\Enums\ActivityType;
use App\Enums\ContentType;
use App\Enums\EnrollmentStatus;
use App\Enums\GradeSource;
use App\Enums\ImmersiveProvider;
use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Invariantes de los enums de dominio: toda variante tiene etiqueta y los
 * `options()` que alimentan los `<select>` cubren exactamente las variantes.
 */
class EnumTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function labelledEnums(): iterable
    {
        yield 'AcademicStatus' => [AcademicStatus::class];
        yield 'ActivityType' => [ActivityType::class];
        yield 'ContentType' => [ContentType::class];
        yield 'EnrollmentStatus' => [EnrollmentStatus::class];
        yield 'GradeSource' => [GradeSource::class];
        yield 'ImmersiveProvider' => [ImmersiveProvider::class];
        yield 'QuestionType' => [QuestionType::class];
        yield 'RoleSlug' => [RoleSlug::class];
        yield 'UserStatus' => [UserStatus::class];
    }

    #[DataProvider('labelledEnums')]
    public function test_every_case_has_a_non_empty_label(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $this->assertNotSame('', trim($case->label()), "{$enum}::{$case->name} sin etiqueta");
        }
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function optionEnums(): iterable
    {
        yield 'AcademicStatus' => [AcademicStatus::class];
        yield 'ActivityType' => [ActivityType::class];
        yield 'ContentType' => [ContentType::class];
        yield 'EnrollmentStatus' => [EnrollmentStatus::class];
        yield 'ImmersiveProvider' => [ImmersiveProvider::class];
        yield 'QuestionType' => [QuestionType::class];
        yield 'UserStatus' => [UserStatus::class];
    }

    #[DataProvider('optionEnums')]
    public function test_options_map_covers_every_case(string $enum): void
    {
        $options = $enum::options();
        $values = array_map(fn ($c) => $c->value, $enum::cases());

        $this->assertEqualsCanonicalizing($values, array_keys($options));
        foreach ($options as $label) {
            $this->assertIsString($label);
            $this->assertNotSame('', trim($label));
        }
    }

    public function test_role_slug_values_are_the_four_system_roles(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'coordinator', 'teacher', 'student'],
            array_map(fn (RoleSlug $r) => $r->value, RoleSlug::cases()),
        );
    }
}
