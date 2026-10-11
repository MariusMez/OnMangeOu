<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_upload_form_is_displayed(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Analyser ma carte')
            ->assertSee('Analyses récentes');
    }
}
