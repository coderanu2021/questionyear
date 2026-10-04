<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:create-admin {email} {--name=Admin} {--generate : Generate a random initial password}')]
#[Description('Create an admin with a securely prompted password')]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::where('email', $this->argument('email'))->exists()) {
            $this->error('This email already exists. No account was changed.');

            return self::FAILURE;
        }
        $password = $this->option('generate') ? Str::password(20) : $this->secret('Choose an admin password (at least 12 characters)');
        if (! is_string($password) || strlen($password) < 12 || ! filter_var($this->argument('email'), FILTER_VALIDATE_EMAIL)) {
            $this->error('Use a valid email and a password of at least 12 characters.');

            return self::FAILURE;
        }
        $user = User::firstOrNew(['email' => $this->argument('email')]);
        $user->fill(['name' => $this->option('name'), 'password' => $password]);
        $user->role = 'admin';
        $user->status = 'active';
        $user->save();
        $this->info('Admin ready. Log in at /login and open /admin.');
        if ($this->option('generate')) {
            $this->line('Initial password: '.$password);
        }

        return self::SUCCESS;
    }
}
