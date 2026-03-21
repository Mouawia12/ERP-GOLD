<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root entry should redirect to the localized application entrypoint.
     */
    public function test_the_application_redirects_from_root(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
