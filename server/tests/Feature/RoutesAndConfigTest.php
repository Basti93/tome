<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoutesAndConfigTest extends TestCase
{
    public function test_no_route_has_a_closure_outside_of_its_uses_action(): void
    {
        // `Route::get('x', ['as' => 'name', function () {}])` puts the closure in a numeric key that
        // `php artisan route:cache` cannot export: the cached file then fails to load on every request
        $broken = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->getAction() as $key => $value) {
                if ($value instanceof \Closure && $key !== 'uses') {
                    $broken[] = $route->uri();
                }
            }
        }

        $this->assertSame([], $broken);
    }

    private function setEnv(string $key, ?string $value): void
    {
        foreach ([&$_ENV, &$_SERVER] as &$bag) {
            $value === null ? $bag[$key] = null : $bag[$key] = $value;
            if ($value === null) {
                unset($bag[$key]);
            }
        }
        $value === null ? putenv($key) : putenv("$key=$value");
        Env::getRepository()->clear($key);
        if ($value !== null) {
            Env::getRepository()->set($key, $value);
        }
    }

    public function test_mail_config_reads_mail_mailer_and_falls_back_to_the_legacy_mail_driver(): void
    {
        $old = [env('MAIL_MAILER'), env('MAIL_DRIVER')];

        try {
            $this->setEnv('MAIL_MAILER', null);
            $this->setEnv('MAIL_DRIVER', 'sendmail');
            $this->assertSame('sendmail', (require base_path('config/mail.php'))['default']);

            $this->setEnv('MAIL_MAILER', 'log');
            $this->assertSame('log', (require base_path('config/mail.php'))['default']);

            $this->setEnv('MAIL_DRIVER', null);
            $this->setEnv('MAIL_MAILER', null);
            $this->assertSame('smtp', (require base_path('config/mail.php'))['default']);
        } finally {
            $this->setEnv('MAIL_MAILER', $old[0] === null ? null : (string) $old[0]);
            $this->setEnv('MAIL_DRIVER', $old[1] === null ? null : (string) $old[1]);
        }
    }
}
