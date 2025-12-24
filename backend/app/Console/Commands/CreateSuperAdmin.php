<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:create-super-admin 
                            {--email= : Email address for the super admin}
                            {--password= : Password for the super admin}
                            {--first-name= : First name}
                            {--last-name= : Last name}
                            {--school-id= : School ID (optional, leave empty for system super admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user for the SMS system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating Super Admin User...');
        $this->newLine();

        // Collect user information
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');
        $firstName = $this->option('first-name') ?: $this->ask('First name', 'Super');
        $lastName = $this->option('last-name') ?: $this->ask('Last name', 'Admin');
        $schoolId = $this->option('school-id');

        // Validate input
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'school_id' => $schoolId,
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'school_id' => ['nullable', 'exists:schools,id'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return Command::FAILURE;
        }

        // Check if super admin role exists
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->error('Super Admin role not found. Please run the role seeder first.');
            $this->info('Run: php artisan db:seed --class=RoleSeeder');
            return Command::FAILURE;
        }

        // Check if school exists (if provided)
        if ($schoolId) {
            $school = \App\Models\School::find($schoolId);
            if (!$school) {
                $this->error("School with ID {$schoolId} not found.");
                return Command::FAILURE;
            }
            $this->info("School: {$school->name}");
        } else {
            $this->info('School: System Super Admin (no school assigned)');
        }

        // Confirm creation
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['First Name', $firstName],
                ['Last Name', $lastName],
                ['School ID', $schoolId ?? 'None (System Super Admin)'],
            ]
        );

        if (!$this->confirm('Create this super admin user?', true)) {
            $this->info('Operation cancelled.');
            return Command::FAILURE;
        }

        try {
            // Create user
            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'school_id' => $schoolId,
                'is_active' => true,
            ]);

            // Assign super admin role
            $user->roles()->attach($superAdminRole->id);

            $this->newLine();
            $this->info('✓ Super Admin created successfully!');
            $this->newLine();
            $this->table(
                ['ID', 'Email', 'Name', 'Roles'],
                [
                    [
                        $user->id,
                        $user->email,
                        "{$user->first_name} {$user->last_name}",
                        $user->roles()->pluck('name')->join(', '),
                    ],
                ]
            );

            $this->newLine();
            $this->info('You can now login with:');
            $this->line("  Email: {$email}");
            $this->line("  Password: [the password you entered]");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create super admin: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

