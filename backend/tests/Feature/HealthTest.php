<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthTest extends TestCase
{
    #[Test]
    public function it_reports_ok_without_touching_the_database(): void
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame(0, $queries);
    }
}
