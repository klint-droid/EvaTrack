<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Redirect mysql_v2 connection to SQLite in-memory during tests
        config(['database.connections.mysql_v2' => config('database.connections.sqlite')]);

        parent::setUp();
    }
}
