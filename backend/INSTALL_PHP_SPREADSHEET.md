# Installing PhpSpreadsheet

## Issue: Missing ext-gd Extension

PhpSpreadsheet requires the `ext-gd` PHP extension for image processing. This extension is usually available in XAMPP but may be disabled.

## Solution 1: Enable ext-gd Extension (Recommended)

### For XAMPP (Windows):

1. **Open `php.ini` file:**
   - Location: `C:\xampp\php\php.ini`
   - Or find it by running: `php --ini`

2. **Find and uncomment the GD extension:**
   ```ini
   ;extension=gd
   ```
   Change to:
   ```ini
   extension=gd
   ```

3. **Restart Apache** in XAMPP Control Panel

4. **Verify extension is loaded:**
   ```bash
   php -m | grep gd
   ```
   You should see `gd` in the output.

5. **Install PhpSpreadsheet:**
   ```bash
   cd backend
   composer require phpoffice/phpspreadsheet
   ```

## Solution 2: Use Compatible Version (If ext-gd cannot be enabled)

If you cannot enable ext-gd, you can install a version that doesn't require it for basic operations:

```bash
cd backend
composer require phpoffice/phpspreadsheet:^2.0 --ignore-platform-req=ext-gd
```

**Note:** This will work for basic Excel read/write operations, but image processing features will not be available.

## Solution 3: Install Without Image Support

If you only need basic Excel functionality (read/write cells, no images), you can use:

```bash
cd backend
composer require phpoffice/phpspreadsheet:^2.0 --ignore-platform-reqs
```

## Verify Installation

After installation, verify it works:

```bash
php artisan tinker
```

Then in tinker:
```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
$spreadsheet = new Spreadsheet();
echo "PhpSpreadsheet installed successfully!";
```

## Alternative: Use Laravel Excel Package

If PhpSpreadsheet continues to cause issues, you can use the Laravel Excel package instead:

```bash
composer require maatwebsite/excel
```

This package wraps PhpSpreadsheet and provides Laravel-specific features, but still requires ext-gd.

## Check PHP Extensions

To see all loaded PHP extensions:
```bash
php -m
```

To check if a specific extension is loaded:
```bash
php -m | grep gd
```

## Troubleshooting

### "extension=gd not found"
- Make sure you're editing the correct `php.ini` file
- Check that the GD DLL file exists in your PHP ext folder
- For XAMPP, the DLL should be at: `C:\xampp\php\ext\php_gd2.dll`

### "Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found"
- Run `composer dump-autoload`
- Clear Laravel cache: `php artisan config:clear`

### Still having issues?
- Check PHP version: `php -v` (should be 8.2+)
- Check Composer version: `composer --version`
- Try updating Composer: `composer self-update`

