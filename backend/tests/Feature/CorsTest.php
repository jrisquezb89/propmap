<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CorsTest extends TestCase
{
    #[Test]
    public function the_local_frontend_origin_is_allowed(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:5173'])
            ->getJson('/api/health')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    #[Test]
    public function preflight_from_the_frontend_succeeds(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/properties')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    #[Test]
    public function an_unknown_origin_is_not_echoed_back(): void
    {
        // The browser blocks the response because the allowed origin doesn't match the caller.
        $response = $this->withHeaders(['Origin' => 'https://evil.example'])->getJson('/api/health');

        $this->assertNotSame('https://evil.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
