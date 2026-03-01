<?php

namespace Tests;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    use DatabaseMigrations;

    /** @var Process|null */
    protected static ?Process $serverProcess = null;

    /**
     * Run a fresh migration + seed before each test method.
     */
    protected function setUp(): void
    {
        // Ensure the SQLite file exists before migrations try to open it
        $dbPath = env('DB_DATABASE');
        if ($dbPath && $dbPath !== ':memory:' && ! file_exists($dbPath)) {
            touch($dbPath);
        }

        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'DuskSeeder']);
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }

        static::startDevServer();
    }

    /**
     * Start the Laravel dev server if nothing is already listening on APP_URL.
     */
    protected static function startDevServer(): void
    {
        $appUrl = env('APP_URL', 'http://127.0.0.1:8000');
        $host   = parse_url($appUrl, PHP_URL_HOST) ?? '127.0.0.1';
        $port   = parse_url($appUrl, PHP_URL_PORT) ?? 8000;

        // If the port is already open, assume another server is running.
        $connection = @fsockopen($host, (int) $port, $errno, $errstr, 0.5);
        if ($connection) {
            fclose($connection);
            return;
        }

        static::$serverProcess = new Process(
            [PHP_BINARY, 'artisan', 'serve', "--host={$host}", "--port={$port}"],
            dirname(__DIR__),   // project root — base_path() is unavailable in BeforeClass
            ['APP_ENV' => 'dusk.local']  // loads .env.dusk.local (same DB as test runner)
        );
        static::$serverProcess->start();

        // Wait up to 3 s for the server to come up
        $deadline = microtime(true) + 3.0;
        while (microtime(true) < $deadline) {
            usleep(100_000);
            $conn = @fsockopen($host, (int) $port, $errno, $errstr, 0.1);
            if ($conn) {
                fclose($conn);
                break;
            }
        }
    }

    /**
     * Stop the dev server after the entire test suite finishes.
     */
    #[AfterClass]
    public static function stopDevServer(): void
    {
        if (static::$serverProcess && static::$serverProcess->isRunning()) {
            static::$serverProcess->stop(3);
        }
        static::$serverProcess = null;
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    // ── Auth helpers ────────────────────────────────────────────────────────

    /**
     * Log the browser in as the seeded Admin user.
     */
    protected function loginAsAdmin(Browser $browser): Browser
    {
        $user = User::where('email', 'admin@test.com')->firstOrFail();

        return $browser->loginAs($user);
    }

    /**
     * Log the browser in as the seeded Financial Secretary user.
     */
    protected function loginAsFS(Browser $browser): Browser
    {
        $user = User::where('email', 'finsec@test.com')->firstOrFail();

        return $browser->loginAs($user);
    }

    /**
     * Log the browser in as one of the seeded Member users.
     *
     * @param  string  $email  member.a@test.com | member.b@test.com | member.c@test.com
     */
    protected function loginAsMember(Browser $browser, string $email): Browser
    {
        $user = User::where('email', $email)->firstOrFail();

        return $browser->loginAs($user);
    }
}
