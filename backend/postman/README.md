# SMS API Postman Collection

This Postman collection contains all API endpoints for the School Management System.

## Import Instructions

1. Open Postman
2. Click **Import** button (top left)
3. Select the `SMS_API_Collection.json` file
4. The collection will be imported with all endpoints organized by module

## Environment Variables

After importing, set up environment variables:

1. Create a new environment in Postman
2. Add these variables:

| Variable | Initial Value | Current Value |
|---------|--------------|---------------|
| `base_url` | `http://localhost:8000` | Your API base URL |
| `auth_token` | (empty) | Will be auto-filled after login |
| `school_id` | `1` | Your school ID |
| `user_id` | (empty) | Will be auto-filled after login |

## Quick Start

1. **Set Environment**: Select your environment in Postman
2. **Update base_url**: Change `base_url` if your Laravel server runs on a different port
3. **Login First**: 
   - Go to `Authentication > Login`
   - Update email/password in the request body
   - Send the request
   - The `auth_token` will be automatically saved to your environment
4. **Test Other Endpoints**: All other endpoints will use the saved token automatically

## Collection Structure

### Authentication
- Register
- Login (auto-saves token)
- Get Current User
- Refresh Token
- Logout

### Students
- List Students
- Create Student
- Get Student
- Update Student
- Delete Student
- Get Student Results
- Get Student Attendance

### Teachers
- List Teachers
- Create Teacher
- Get Teacher
- Get Teacher Classes

### Classes
- List Classes
- Create Class
- Get Class Students
- Get Class Subjects

### Subjects
- List Subjects
- Create Subject

### Attendance
- List Attendance
- Mark Attendance
- Get Student Attendance
- Get Class Attendance

### Exams & Assessments
- List Exams
- Create Exam
- List Assessments
- Create Assessment

### Results
- List Results
- Enter Results
- Get Student Results
- Get Student Term Results

### Report Cards
- Generate Report Card
- Get Report Card

### Terms
- List Terms
- Create Term
- Activate Term
- Close Term
- Archive Term

### Academic Years
- List Academic Years
- Create Academic Year
- Activate Academic Year

### Parent Portal
- Get My Children
- Get My Subscriptions
- Get My Payments

### Subscriptions
- List Subscriptions (Admin)
- Get Student Subscriptions
- Get Parent Subscriptions

### Payments
- List Payments (Admin)
- Create Payment
- Verify Payment
- Payment Webhook

### Notifications
- List Notifications
- Get Unread Notifications
- Mark Notification as Read
- Mark All as Read

### Profile
- Get Profile
- Update Profile

## Notes

- All protected endpoints require the `Authorization: Bearer {token}` header
- The Login request automatically saves the token to environment variables
- Most endpoints require `school_id` as a query parameter (added automatically via environment variable)
- Update the `school_id` environment variable to test with different schools

## Testing Tips

1. **Start with Authentication**: Always login first to get a valid token
2. **Check Response Codes**: 
   - `200` = Success
   - `201` = Created
   - `401` = Unauthorized (check token)
   - `403` = Forbidden (check role permissions)
   - `404` = Not Found
   - `422` = Validation Error
3. **Update IDs**: Replace placeholder IDs (like `:id`, `:studentId`) with actual IDs from your database
4. **Test Role-Based Access**: Try accessing admin endpoints with a parent/teacher account to test RBAC

## Common Issues

**401 Unauthorized**: 
- Token expired or invalid
- Solution: Login again to get a new token

**403 Forbidden**:
- User doesn't have required role
- Solution: Use an account with the correct role (super_admin, school_admin, etc.)

**404 Not Found**:
- Resource doesn't exist or wrong school_id
- Solution: Check that the resource exists and belongs to the correct school

**422 Validation Error**:
- Missing or invalid request data
- Solution: Check the request body matches the required format

