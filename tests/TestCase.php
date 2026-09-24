<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views load assets via @vite (Tailwind bundle). No manifest is
        // built in CI/testing, so stub Vite out — assertions are text-based.
        $this->withoutVite();

        // Array cache persists in-process across tests. Flush it so a
        // cached slug from one test can never leak into the next test's
        // fresh in-memory database.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }
}
