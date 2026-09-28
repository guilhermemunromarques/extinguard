<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    protected function postWithCsrf(string $uri, array $payload = [], array $headers = [])
    {
        $this->get(route('login'));

        $token = csrf_token();

        return $this
            ->withSession(['_token' => $token])
            ->post($uri, [...$payload, '_token' => $token], $headers);
    }
}
