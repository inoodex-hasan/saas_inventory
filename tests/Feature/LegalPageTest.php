<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPageTest extends TestCase
{
    public function test_privacy_policy_page_renders_new_landing_layout(): void
    {
        $response = $this->get('/privacy-policy');
        $response->assertStatus(200);
        $response->assertSee('legal-section-alt');
        $response->assertSee('YOUR INFORMATION');
        $response->assertSee('Privacy Policy');
        $response->assertDontSee('ambient-glow');

        $altResponse = $this->get('/privacy');
        $altResponse->assertStatus(200);
        $altResponse->assertSee('legal-section-alt');
    }

    public function test_terms_page_renders_new_landing_layout(): void
    {
        $response = $this->get('/terms-and-conditions');
        $response->assertStatus(200);
        $response->assertSee('legal-section');
        $response->assertSee('LEGAL INFORMATION');
        $response->assertSee('Terms of Service');

        $altResponse = $this->get('/terms');
        $altResponse->assertStatus(200);
        $altResponse->assertSee('legal-section');
    }

    public function test_pricing_page_renders_comparison_table(): void
    {
        $response = $this->get('/pricing');
        $response->assertStatus(200);
        $response->assertSee('pricing-comparison-section');
        $response->assertSee('Compare plans at a glance');
        $response->assertSee('pricing-comparison-table');
        $response->assertSee('Starter');
        $response->assertSee('Professional');
        $response->assertSee('Enterprise');
    }
}
