# Excel Import/Export Feature

This feature allows administrators to bulk import students and teachers using Excel files.

## Installation

1. **Install PhpSpreadsheet package:**
   ```bash
   cd backend
   composer require phpoffice/phpspreadsheet
   ```

2. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## API Endpoints

### Download Templates

- **GET** `/api/excel/templates/students` - Download students import template
- **GET** `/api/excel/templates/teachers` - Download teachers import template

### Import Data

- **POST** `/api/excel/import/students` - Import students from Excel file
- **POST** `/api/excel/import/teachers` - Import teachers from Excel file

**Required Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data`

**Request Body:**
- `file` (required): Excel file (.xlsx or .xls, max 10MB)
- `school_id` (required): Query parameter for school ID

## Excel Template Format

### Students Template

| Column | Field | Required | Format | Example |
|--------|-------|----------|--------|---------|
| A | Student Number | Yes | String (max 50) | STU001 |
| B | First Name | Yes | String (max 100) | John |
| C | Last Name | Yes | String (max 100) | Doe |
| D | Middle Name | No | String (max 100) | Michael |
| E | Date of Birth | No | Date (YYYY-MM-DD) | 2010-05-15 |
| F | Gender | No | male/female/other | male |
| G | Phone | No | String (max 20) | +233241234567 |
| H | Email | No | Email | john.doe@example.com |
| I | Address | No | String | 123 Main Street, Accra |

### Teachers Template

| Column | Field | Required | Format | Example |
|--------|-------|----------|--------|---------|
| A | First Name | Yes | String (max 100) | Sarah |
| B | Last Name | Yes | String (max 100) | Johnson |
| C | Email | Yes | Email (unique) | sarah.johnson@school.com |
| D | Password | Yes | String (min 8) | SecurePass123 |
| E | Staff Number | No | String (max 50) | TCH001 |
| F | Qualification | No | String (max 255) | PhD in Mathematics |
| G | Specialization | No | String (max 255) | Mathematics, Physics |
| H | Hire Date | No | Date (YYYY-MM-DD) | 2020-01-15 |

## Import Process

1. **Download Template**: Click "Download Template" button to get the Excel template
2. **Fill Data**: Fill the template with your data (keep the header row)
3. **Upload File**: Select the filled Excel file and click "Import"
4. **Review Results**: The system will show:
   - Total rows processed
   - Success count
   - Error count (if any)
   - Detailed error messages for failed rows

## Validation Rules

### Students
- Student Number must be unique
- First Name and Last Name are required
- Date of Birth must be a valid date format (YYYY-MM-DD)
- Gender must be one of: male, female, other
- Email must be a valid email format

### Teachers
- Email must be unique across all users
- Password must be at least 8 characters
- First Name and Last Name are required
- Hire Date must be a valid date format (YYYY-MM-DD)

## Error Handling

The import process:
- Processes all rows even if some fail
- Returns detailed error messages for each failed row
- Uses database transactions to ensure data integrity
- Rolls back all changes if all rows fail validation

## Response Format

**Success Response:**
```json
{
  "success": true,
  "message": "Import completed. 50 students imported successfully, 2 errors.",
  "data": {
    "total_rows": 52,
    "success_count": 50,
    "error_count": 2,
    "errors": [
      {
        "row": 5,
        "student_number": "STU005",
        "errors": ["The student number has already been taken."]
      }
    ]
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "All rows failed validation. No students were imported.",
  "errors": [
    {
      "row": 2,
      "student_number": "STU001",
      "errors": ["The first name field is required."]
    }
  ],
  "data": {
    "total_rows": 10,
    "success_count": 0,
    "error_count": 10
  }
}
```

## Frontend Usage

The Excel import feature is integrated into:
- Students List Page (`/app/students`)
- Teachers List Page (`/app/teachers`)

Click the "Import Excel" button to open the import dialog.

## Security

- Only users with `super_admin` or `school_admin` roles can import data
- All imports are scoped to the user's school
- File size is limited to 10MB
- Only .xlsx and .xls file formats are accepted
- All data is validated before insertion

## Troubleshooting

### "File rejected" error
- Ensure file is .xlsx or .xls format
- Check file size is under 10MB
- Verify file is not corrupted

### "All rows failed validation"
- Check that required fields are filled
- Verify data formats match the template
- Ensure no duplicate student numbers/emails

### "School ID is required"
- Make sure you're logged in with a school admin account
- The school_id is automatically added from your session

## Future Enhancements

- Support for importing Classes, Subjects, and other entities
- Export functionality to download existing data
- Batch update support
- Import history and audit logs

