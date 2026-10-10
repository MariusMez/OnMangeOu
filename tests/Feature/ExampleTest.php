<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_upload_form_is_displayed(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Analyser ma carte');
    }
}
