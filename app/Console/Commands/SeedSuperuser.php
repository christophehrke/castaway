<?php

namespace App\Console\Commands;

use App\Models\CommandRun;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedSuperuser extends Command
{
    protected $signature = 'admin:seed-superuser {--email=admin@flowcast.io} {--password=}';

    protected $description = 'Create a superadmin user if one does not already exist';

    public function handle(): int
    {
        $commandRun = CommandRun::create([
            'command' => $this->getName(),
            'arguments' => $this->options(),
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        try {
            $email = $this->option('email');
            $password = $this->option('password') ?: Str::random(16);

            $existing = User::where('email', $email)->first();

            if ($existing) {
                $this->info("User with email {$email} already exists. Skipping.");

                $commandRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'records_processed' => 0,
                    'output' => 'User already exists, skipped.',
                ]);

                return self::SUCCESS;
            }

            $organization = Organization::firstOrCreate(
                ['slug' => 'admin'],
                ['name' => 'Admin', 'settings' => []]
            );

            $user = User::create([
                'organization_id' => $organization->id,
                'name' => 'Super Admin',
                'email' => $email,
                'password' => $password,
                'role' => 'admin',
                'is_superadmin' => true,
            ]);

            $organization->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);

            $this->info("Superadmin created: {$email}");

            if (!$this->option('password')) {
                $this->info("Generated password: {$password}");
            }

            $commandRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'records_processed' => 1,
                'output' => "Superadmin created: {$email}",
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");

            $commandRun->update([
                'status' => 'failed',
                'completed_at' => now(),
                'records_failed' => 1,
                'output' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
