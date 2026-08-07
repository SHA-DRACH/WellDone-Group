<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The homepage renders for a visitor who is not signed in.
 *
 * Replaces the scaffold's ExampleTest, which called `/` without a database
 * and so failed on the first query the page makes. A smoke test that cannot
 * pass is worse than none: it trains everybody to ignore a red suite.
 */
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_renders_for_a_visitor(): void
    {
        // No seed data on purpose: the page has to cope with empty tables.
        $this->get('/')->assertOk();
    }
}
