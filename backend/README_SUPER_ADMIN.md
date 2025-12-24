# Creating a Super Admin

This guide explains how to create a super admin user for the School Management System.

## Method 1: Using Artisan Command (Recommended)

Run the following command to create a super admin interactively:

```bash
php artisan sms:create-super-admin
```

The command will prompt you for:
- Email address
- Password (min 8 characters)
- First name (defaults to "Super")
- Last name (defaults to "Admin")
- School ID (optional - leave empty for system super admin)

### Using Options (Non-Interactive)

You can also provide all information via command options:

```bash
php artisan sms:create-super-admin \
    --email=admin@school.com \
    --password=SecurePassword123 \
    --first-name="Super" \
    --last-name="Admin" \
    --school-id=1
```

### System Super Admin (No School)

To create a system-level super admin (not tied to any school):

```bash
php artisan sms:create-super-admin \
    --email=system@admin.com \
    --password=SecurePassword123 \
    --first-name="System" \
    --last-name="Admin"
```

(Simply omit the `--school-id` option)

## Method 2: Using Tinker

You can also create a super admin using Laravel Tinker:

```bash
php artisan tinker
```

Then run:

```php
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

// Create user
$user = User::create([
    'email' => 'admin@school.com',
    'password' => Hash::make('SecurePassword123'),
    'first_name' => 'Super',
    'last_name' => 'Admin',
    'school_id' => 1, // or null for system super admin
    'is_active' => true,
]);

// Get super admin role
$superAdminRole = Role::where('name', 'super_admin')->first();

// Assign role
$user->roles()->attach($superAdminRole->id);

echo "Super Admin created: {$user->email}\n";
```

## Prerequisites

Before creating a super admin, ensure:

1. **Database is migrated**:
   ```bash
   php artisan migrate
   ```

2. **Roles are seeded**:
   ```bash
   php artisan db:seed --class=RoleSeeder
   ```

3. **School exists** (if assigning to a school):
   - Make sure the school exists in the database
   - You can check existing schools with: `php artisan tinker` then `\App\Models\School::all()`

## Super Admin Capabilities

A super admin has:
- Access to all schools (if system super admin) or full access to assigned school
- Can manage all users, students, teachers, classes
- Can manage terms, academic years, and settings
- Can view all subscriptions and payments
- Bypasses all role-based access restrictions

## Troubleshooting

### "Super Admin role not found"
Run the role seeder:
```bash
php artisan db:seed --class=RoleSeeder
```

### "Email already exists"
The email is already registered. Use a different email or update the existing user.

### "School not found"
If you provided a `--school-id`, make sure the school exists in the database.

## Security Notes

- Use a strong password (min 8 characters, but recommend 12+)
- Store credentials securely
- Change default passwords immediately
- Consider using environment variables for initial setup in production

