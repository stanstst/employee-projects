<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_the_home_page_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_api_returns_a_json_status(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk()->assertJson(['status' => 'ok']);
    }
}
