<?php
namespace Sosupp\SlimerBilling\Console;

use Illuminate\Console\Command;

class SlimerBillingInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slimer:billing-install
                            {--m|migrate : Automatically run migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform initial housekeeping for the Slimer Billing package (publish config, migrations files, views, .env additions, etc).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🚀 Installing Slimer Billing");

        $this->publishAssets();
        $this->updateEnv();
        
        // $this->call('slimer:landlord-install');

        $this->updateEnv([

        ]);

        $this->info("✅ Slimer Tenancy installation complete.");

        return self::SUCCESS;
    }

    private function publishAssets(): void
    {
        $this->line("📄 Publishing config...");
        $this->callSilent('vendor:publish', [
            '--tag' => 'slimer-billing-config',
            '--force' => true,
        ]);

        $this->line("📦 Publishing migration files...");
        $this->callSilent('vendor:publish', [
            '--tag' => 'slimer-billing-migrations',
            '--force' => true,
        ]);
    }

    private function updateEnv(array $data = []): void
    {
        $this->line("📝 Updating .env file...");

        $domain = str(env('APP_NAME'))->camel()->lower()->value();

        $updates = $data;

        if(empty($data)){
            $updates = [

            ];
        }

        if(empty($updates)){
            $this->info('No .env variables to update...');
            return;
        }

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error("❌ .env file not found!");
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($updates as $key => $value) {

            // If key exists, replace it
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            }
            // Otherwise, append it
            else {
                $envContent .= PHP_EOL."{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);

        $this->info("🔧 Environment variables updated if any.");
    }

    private function runLandlordMigrations(): void
    {
        if ($this->option('migrate')) {
            $this->line("🛠  Running billing migrations...");

            $this->call('migrate', [
                '--path' => 'database/migrations/landlord',
            ]);

            $this->call('migrate', [
                '--path' => 'database/migrations/tenant',
            ]);
        }
    }
}
