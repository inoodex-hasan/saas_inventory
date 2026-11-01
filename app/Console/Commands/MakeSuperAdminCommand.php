<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin 
                            {email? : Email address of the super admin} 
                            {--password= : Password for new user (default: admin12345)} 
                            {--name= : Name of the user (default: Super Admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Super Admin or promote an existing user to Super Admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Enter the email address for Super Admin');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$email}");
            return Command::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->is_super_admin = true;
            $user->save();
            $this->info("✓ User [{$email}] has been promoted to Platform Super Admin!");
        } else {
            $name = $this->option('name') ?: $this->ask('Enter name for the Super Admin', 'Super Admin');
            $password = $this->option('password') ?: $this->secret('Enter password for the Super Admin') ?: 'admin12345';

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $user->is_super_admin = true;
            $user->save();

            $this->info("✓ Super Admin user [{$email}] created successfully!");
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Is Super Admin?'],
            [[$user->id, $user->name, $user->email, 'YES']]
        );

        return Command::SUCCESS;
    }
}
