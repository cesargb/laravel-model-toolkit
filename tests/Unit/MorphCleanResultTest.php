<?php

namespace Cesargb\ModelToolkit\Tests\Unit;

use Cesargb\ModelToolkit\MorphCleanResult;
use Cesargb\ModelToolkit\Tests\TestCase;

class MorphCleanResultTest extends TestCase
{
    public function test_success_reports_deleted_count_and_no_error(): void
    {
        $result = MorphCleanResult::success(3);

        $this->assertTrue($result->succeeded());
        $this->assertFalse($result->failed());
        $this->assertSame(3, $result->deletedCount());
        $this->assertNull($result->error());
    }

    public function test_failure_reports_error_and_zero_deleted(): void
    {
        $result = MorphCleanResult::failure('something went wrong');

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertSame(0, $result->deletedCount());
        $this->assertSame('something went wrong', $result->error());
    }
}
