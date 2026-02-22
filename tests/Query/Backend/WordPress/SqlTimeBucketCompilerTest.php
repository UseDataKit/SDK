<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress;

use DataKit\DataViews\Query\Backend\WordPress\SqlTimeBucketCompiler;
use DataKit\DataViews\Query\TimeBucket;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SQL time bucket compilation.
 */
final class SqlTimeBucketCompilerTest extends TestCase
{
    private SqlTimeBucketCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new SqlTimeBucketCompiler();
    }

    public function test_hour(): void
    {
        $result = $this->compiler->compile('e.date_created', TimeBucket::Hour);
        self::assertSame("DATE_FORMAT(e.date_created, '%%Y-%%m-%%d %%H:00:00')", $result);
    }

    public function test_day(): void
    {
        $result = $this->compiler->compile('e.date_created', TimeBucket::Day);
        self::assertSame("DATE_FORMAT(e.date_created, '%%Y-%%m-%%d')", $result);
    }

    public function test_week(): void
    {
        $result = $this->compiler->compile('e.date_created', TimeBucket::Week);
        self::assertSame("DATE_FORMAT(DATE_SUB(e.date_created, INTERVAL WEEKDAY(e.date_created) DAY), '%%Y-%%m-%%d')", $result);
    }

    public function test_month(): void
    {
        $result = $this->compiler->compile('e.date_created', TimeBucket::Month);
        self::assertSame("DATE_FORMAT(e.date_created, '%%Y-%%m-01')", $result);
    }

    public function test_quarter(): void
    {
        $result = $this->compiler->compile('e.date_created', TimeBucket::Quarter);
        self::assertSame("CONCAT(YEAR(e.date_created), '-Q', QUARTER(e.date_created))", $result);
    }

    public function test_year(): void
    {
        $result = $this->compiler->compile('e.date_created', TimeBucket::Year);
        self::assertSame("DATE_FORMAT(e.date_created, '%%Y-01-01')", $result);
    }
}
