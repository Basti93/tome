<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards against re-introducing hardcoded credentials, env() calls outside config and
 * unbounded personal data in the logs (security audit A8/A9).
 */
class ConfigHygieneTest extends TestCase
{
    private function source(string $relative): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/'.$relative);
    }

    public function test_database_config_has_no_hardcoded_credentials(): void
    {
        $source = $this->source('config/database.php');

        preg_match_all("/env\\('DB_(?:USERNAME|PASSWORD|DATABASE)', '([^']*)'\\)/", $source, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $default) {
            $this->assertContains($default, ['', 'forge'], "unexpected default '$default'");
        }
        $this->assertStringNotContainsString('vereinsverwaltung', $source);
        $this->assertStringNotContainsString("'root'", $source);
    }

    public function test_logging_defaults_to_the_daily_channel_with_14_days_retention(): void
    {
        $source = $this->source('config/logging.php');

        $this->assertStringContainsString("env('LOG_CHANNEL', 'daily')", $source);
        $this->assertStringContainsString("(int) env('LOG_DAILY_DAYS', 14)", $source);
    }

    public function test_env_is_only_read_in_config_files(): void
    {
        $offenders = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && preg_match('/\benv\(/', file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders);
    }
}
