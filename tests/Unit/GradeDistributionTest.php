<?php

namespace Tests\Unit;

use App\Services\Analytics\PerformanceAnalyticsService;
use App\Services\Analytics\ProgressCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GradeDistributionTest extends TestCase
{
    private function service(): PerformanceAnalyticsService
    {
        return new PerformanceAnalyticsService(new ProgressCalculator);
    }

    public function test_percentages_are_bucketed_by_ten_point_bands(): void
    {
        $dist = collect($this->service()->distribution(new Collection([0, 59, 60, 69.9, 70, 88, 90, 100])))
            ->keyBy('label');

        $this->assertSame(2, $dist['0–59']['value']);   // 0, 59
        $this->assertSame(2, $dist['60–69']['value']);  // 60, 69.9
        $this->assertSame(1, $dist['70–79']['value']);  // 70
        $this->assertSame(1, $dist['80–89']['value']);  // 88
        $this->assertSame(2, $dist['90–100']['value']); // 90, 100
    }

    public function test_empty_input_yields_all_zero_buckets(): void
    {
        $dist = $this->service()->distribution(new Collection);

        $this->assertSame(0, collect($dist)->sum('value'));
        $this->assertCount(5, $dist);
    }
}
