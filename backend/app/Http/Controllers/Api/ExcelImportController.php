<?php

namespace App\Http\Controllers\Api;

use App\Models\Student;
use App\Models\School;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelImportController extends BaseApiController
{
    /**
     * Download Excel template for students
     */
    public function downloadStudentTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Student Number',
            'B1' => 'First Name',
            'C1' => 'Last Name',
            'D1' => 'Middle Name',
            'E1' => 'Date of Birth (YYYY-MM-DD)',
            'F1' => 'Gender (male/female/other)',
            'G1' => 'Phone',
            'H1' => 'Email',
            'I1' => 'Address',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(30);

        // Add example row
        $sheet->setCellValue('A2', 'STU001');
        $sheet->setCellValue('B2', 'John');
        $sheet->setCellValue('C2', 'Doe');
        $sheet->setCellValue('D2', 'Michael');
        $sheet->setCellValue('E2', '2010-05-15');
        $sheet->setCellValue('F2', 'male');
        $sheet->setCellValue('G2', '+233241234567');
        $sheet->setCellValue('H2', 'john.doe@example.com');
        $sheet->setCellValue('I2', '123 Main Street, Accra');

        // Freeze header row
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $filename = 'students_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import students from Excel file
     */
    public function importStudents(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], // 10MB max
        ]);

        $schoolId = $request->get('school_id');
        
        if (!$schoolId) {
            return $this->error('School ID is required', 422);
        }

        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return $this->error('Excel file is empty or missing data rows', 422);
            }

            // Remove header row
            array_shift($rows);

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map row data
                $data = [
                    'student_number' => $row[0] ?? null,
                    'first_name' => $row[1] ?? null,
                    'last_name' => $row[2] ?? null,
                    'middle_name' => $row[3] ?? null,
                    'date_of_birth' => $row[4] ?? null,
                    'gender' => strtolower($row[5] ?? ''),
                    'phone' => $row[6] ?? null,
                    'email' => $row[7] ?? null,
                    'address' => $row[8] ?? null,
                    'school_id' => $schoolId,
                ];

                // Validate row data
                $validator = Validator::make($data, [
                    'student_number' => ['required', 'string', 'max:50', 'unique:students,student_number'],
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'middle_name' => ['nullable', 'string', 'max:100'],
                    'date_of_birth' => ['nullable', 'date'],
                    'gender' => ['nullable', 'in:male,female,other'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'email' => ['nullable', 'email', 'max:255'],
                    'address' => ['nullable', 'string'],
                    'school_id' => ['required', 'exists:schools,id'],
                ]);

                if ($validator->fails()) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'student_number' => $data['student_number'],
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                try {
                    Student::create($data);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'student_number' => $data['student_number'],
                        'errors' => ['Failed to create student: ' . $e->getMessage()],
                    ];
                }
            }

            if ($errorCount > 0 && $successCount === 0) {
                DB::rollBack();
                return $this->error('All rows failed validation. No students were imported.', 422, [
                    'errors' => $errors,
                    'total_rows' => count($rows),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ]);
            }

            DB::commit();

            return $this->success([
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ], "Import completed. {$successCount} students imported successfully, {$errorCount} errors.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to process Excel file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download Excel template for teachers
     */
    public function downloadTeacherTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'First Name',
            'B1' => 'Last Name',
            'C1' => 'Email',
            'D1' => 'Password (min 8 chars)',
            'E1' => 'Staff Number',
            'F1' => 'Qualification',
            'G1' => 'Specialization',
            'H1' => 'Hire Date (YYYY-MM-DD)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Set column widths
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }

        // Add example row
        $sheet->setCellValue('A2', 'Sarah');
        $sheet->setCellValue('B2', 'Johnson');
        $sheet->setCellValue('C2', 'sarah.johnson@school.com');
        $sheet->setCellValue('D2', 'SecurePass123');
        $sheet->setCellValue('E2', 'TCH001');
        $sheet->setCellValue('F2', 'PhD in Mathematics');
        $sheet->setCellValue('G2', 'Mathematics, Physics');
        $sheet->setCellValue('H2', '2020-01-15');

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $filename = 'teachers_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import teachers from Excel file
     */
    public function importTeachers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $schoolId = $request->get('school_id');
        
        if (!$schoolId) {
            return $this->error('School ID is required', 422);
        }

        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return $this->error('Excel file is empty or missing data rows', 422);
            }

            array_shift($rows); // Remove header

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [
                    'first_name' => $row[0] ?? null,
                    'last_name' => $row[1] ?? null,
                    'email' => $row[2] ?? null,
                    'password' => $row[3] ?? null,
                    'staff_number' => $row[4] ?? null,
                    'qualification' => $row[5] ?? null,
                    'specialization' => $row[6] ?? null,
                    'hire_date' => $row[7] ?? null,
                    'school_id' => $schoolId,
                ];

                $validator = Validator::make($data, [
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'email' => ['required', 'email', 'unique:users,email'],
                    'password' => ['required', 'string', 'min:8'],
                    'staff_number' => ['nullable', 'string', 'max:50'],
                    'qualification' => ['nullable', 'string', 'max:255'],
                    'specialization' => ['nullable', 'string', 'max:255'],
                    'hire_date' => ['nullable', 'date'],
                    'school_id' => ['required', 'exists:schools,id'],
                ]);

                if ($validator->fails()) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $data['email'],
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                try {
                    // Create user
                    $user = \App\Models\User::create([
                        'school_id' => $schoolId,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                    ]);

                    // Assign teacher role
                    $teacherRole = \App\Models\Role::where('name', 'teacher')->first();
                    if ($teacherRole) {
                        $user->roles()->attach($teacherRole->id);
                    }

                    // Create teacher profile
                    \App\Models\Teacher::create([
                        'user_id' => $user->id,
                        'staff_number' => $data['staff_number'],
                        'qualification' => $data['qualification'],
                        'specialization' => $data['specialization'],
                        'hire_date' => $data['hire_date'],
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $data['email'],
                        'errors' => ['Failed to create teacher: ' . $e->getMessage()],
                    ];
                }
            }

            if ($errorCount > 0 && $successCount === 0) {
                DB::rollBack();
                return $this->error('All rows failed validation. No teachers were imported.', 422, [
                    'errors' => $errors,
                    'total_rows' => count($rows),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ]);
            }

            DB::commit();

            return $this->success([
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ], "Import completed. {$successCount} teachers imported successfully, {$errorCount} errors.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to process Excel file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download Excel template for classes
     */
    public function downloadClassTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Class Name',
            'B1' => 'Level',
            'C1' => 'Section',
            'D1' => 'Capacity',
            'E1' => 'Academic Year Name',
            'F1' => 'Class Teacher Staff Number (Optional)',
            'G1' => 'Is Active (true/false)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Set column widths
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(25);
        }

        // Add example row
        $sheet->setCellValue('A2', 'Form 1A');
        $sheet->setCellValue('B2', 'Form 1');
        $sheet->setCellValue('C2', 'A');
        $sheet->setCellValue('D2', '30');
        $sheet->setCellValue('E2', '2024/2025');
        $sheet->setCellValue('F2', 'TCH001');
        $sheet->setCellValue('G2', 'true');

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $filename = 'classes_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import classes from Excel file
     */
    public function importClasses(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $schoolId = $request->get('school_id');
        
        if (!$schoolId) {
            return $this->error('School ID is required', 422);
        }

        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return $this->error('Excel file is empty or missing data rows', 422);
            }

            array_shift($rows); // Remove header

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                // Get academic year by name
                $academicYearName = $row[4] ?? null;
                $academicYear = null;
                if ($academicYearName) {
                    $academicYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                        ->where('name', $academicYearName)
                        ->first();
                }

                // Get class teacher by staff number if provided
                $classTeacherId = null;
                $teacherStaffNumber = $row[5] ?? null;
                if ($teacherStaffNumber) {
                    $teacher = \App\Models\Teacher::whereHas('user', function ($q) use ($schoolId) {
                        $q->where('school_id', $schoolId);
                    })->where('staff_number', $teacherStaffNumber)->first();
                    if ($teacher) {
                        $classTeacherId = $teacher->id;
                    }
                }

                $data = [
                    'name' => $row[0] ?? null,
                    'level' => $row[1] ?? null,
                    'section' => $row[2] ?? null,
                    'capacity' => $row[3] ?? null,
                    'academic_year_id' => $academicYear ? $academicYear->id : null,
                    'class_teacher_id' => $classTeacherId,
                    'is_active' => filter_var($row[6] ?? 'true', FILTER_VALIDATE_BOOLEAN),
                    'school_id' => $schoolId,
                ];

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:100'],
                    'level' => ['required', 'string', 'max:50'],
                    'section' => ['nullable', 'string', 'max:50'],
                    'capacity' => ['required', 'integer', 'min:1', 'max:100'],
                    'academic_year_id' => ['required', 'exists:academic_years,id'],
                    'class_teacher_id' => ['nullable', 'exists:teachers,id'],
                    'is_active' => ['sometimes', 'boolean'],
                    'school_id' => ['required', 'exists:schools,id'],
                ]);

                if ($validator->fails()) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'class_name' => $data['name'],
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                try {
                    ClassModel::create($data);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'class_name' => $data['name'],
                        'errors' => ['Failed to create class: ' . $e->getMessage()],
                    ];
                }
            }

            if ($errorCount > 0 && $successCount === 0) {
                DB::rollBack();
                return $this->error('All rows failed validation. No classes were imported.', 422, [
                    'errors' => $errors,
                    'total_rows' => count($rows),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ]);
            }

            DB::commit();

            return $this->success([
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ], "Import completed. {$successCount} classes imported successfully, {$errorCount} errors.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to process Excel file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download Excel template for subjects
     */
    public function downloadSubjectTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Subject Name',
            'B1' => 'Subject Code',
            'C1' => 'Description',
            'D1' => 'Is Core (true/false)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Set column widths
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(25);
        }

        // Add example rows
        $sheet->setCellValue('A2', 'Mathematics');
        $sheet->setCellValue('B2', 'MATH');
        $sheet->setCellValue('C2', 'Core mathematics subject');
        $sheet->setCellValue('D2', 'true');

        $sheet->setCellValue('A3', 'French');
        $sheet->setCellValue('B3', 'FREN');
        $sheet->setCellValue('C3', 'Elective language subject');
        $sheet->setCellValue('D3', 'false');

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $filename = 'subjects_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import subjects from Excel file
     */
    public function importSubjects(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $schoolId = $request->get('school_id');
        
        if (!$schoolId) {
            return $this->error('School ID is required', 422);
        }

        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return $this->error('Excel file is empty or missing data rows', 422);
            }

            array_shift($rows); // Remove header

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [
                    'name' => $row[0] ?? null,
                    'code' => $row[1] ?? null,
                    'description' => $row[2] ?? null,
                    'is_core' => filter_var($row[3] ?? 'false', FILTER_VALIDATE_BOOLEAN),
                    'school_id' => $schoolId,
                ];

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:100'],
                    'code' => ['nullable', 'string', 'max:20'],
                    'description' => ['nullable', 'string'],
                    'is_core' => ['sometimes', 'boolean'],
                    'school_id' => ['required', 'exists:schools,id'],
                ]);

                if ($validator->fails()) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'subject_name' => $data['name'],
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                try {
                    Subject::create($data);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'subject_name' => $data['name'],
                        'errors' => ['Failed to create subject: ' . $e->getMessage()],
                    ];
                }
            }

            if ($errorCount > 0 && $successCount === 0) {
                DB::rollBack();
                return $this->error('All rows failed validation. No subjects were imported.', 422, [
                    'errors' => $errors,
                    'total_rows' => count($rows),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ]);
            }

            DB::commit();

            return $this->success([
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ], "Import completed. {$successCount} subjects imported successfully, {$errorCount} errors.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to process Excel file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download Excel template for class students
     */
    public function downloadClassStudentTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Student Number',
            'B1' => 'First Name',
            'C1' => 'Last Name',
            'D1' => 'Middle Name',
            'E1' => 'Date of Birth (YYYY-MM-DD)',
            'F1' => 'Gender (male/female/other)',
            'G1' => 'Phone',
            'H1' => 'Email',
            'I1' => 'Address',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(30);

        // Add example row
        $sheet->setCellValue('A2', 'STU001');
        $sheet->setCellValue('B2', 'John');
        $sheet->setCellValue('C2', 'Doe');
        $sheet->setCellValue('D2', 'Michael');
        $sheet->setCellValue('E2', '2010-05-15');
        $sheet->setCellValue('F2', 'male');
        $sheet->setCellValue('G2', '+233241234567');
        $sheet->setCellValue('H2', 'john.doe@example.com');
        $sheet->setCellValue('I2', '123 Main Street, Accra');

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $filename = 'class_students_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import class students from Excel file
     */
    public function importClassStudents(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'class_id' => ['required', 'exists:classes,id'],
        ]);

        $schoolId = $request->get('school_id');
        $classId = $request->get('class_id');
        
        if (!$schoolId) {
            return $this->error('School ID is required', 422);
        }

        // Verify class belongs to school
        $class = ClassModel::where('id', $classId)
            ->where('school_id', $schoolId)
            ->first();
        
        if (!$class) {
            return $this->error('Class not found', 404);
        }

        // Get active academic year for the class
        $academicYearId = $class->academic_year_id;
        if (!$academicYearId) {
            return $this->error('Class does not have an academic year assigned', 422);
        }

        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return $this->error('Excel file is empty or missing data rows', 422);
            }

            array_shift($rows); // Remove header

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [
                    'student_number' => !empty($row[0]) ? trim($row[0]) : null,
                    'first_name' => $row[1] ?? null,
                    'last_name' => $row[2] ?? null,
                    'middle_name' => $row[3] ?? null,
                    'date_of_birth' => $row[4] ?? null,
                    'gender' => strtolower($row[5] ?? ''),
                    'phone' => $row[6] ?? null,
                    'email' => $row[7] ?? null,
                    'address' => $row[8] ?? null,
                    'school_id' => $schoolId,
                ];

                // Auto-generate student number if not provided
                if (empty($data['student_number'])) {
                    try {
                        $data['student_number'] = Student::generateStudentNumber($schoolId);
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = [
                            'row' => $rowNumber,
                            'student_number' => 'AUTO-GEN',
                            'errors' => ['Failed to generate student number: ' . $e->getMessage()],
                        ];
                        continue;
                    }
                }

                $validator = Validator::make($data, [
                    'student_number' => ['required', 'string', 'max:50', 'unique:students,student_number'],
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'middle_name' => ['nullable', 'string', 'max:100'],
                    'date_of_birth' => ['nullable', 'date'],
                    'gender' => ['nullable', 'in:male,female,other'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'email' => ['nullable', 'email', 'max:255'],
                    'address' => ['nullable', 'string'],
                    'school_id' => ['required', 'exists:schools,id'],
                ]);

                if ($validator->fails()) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'student_number' => $data['student_number'],
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                try {
                    // Create student
                    $student = Student::create($data);

                    // Create enrollment
                    Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_year_id' => $academicYearId,
                        'enrollment_date' => now()->toDateString(),
                        'status' => 'active',
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'student_number' => $data['student_number'],
                        'errors' => ['Failed to create student: ' . $e->getMessage()],
                    ];
                }
            }

            if ($errorCount > 0 && $successCount === 0) {
                DB::rollBack();
                return $this->error('All rows failed validation. No students were imported.', 422, [
                    'errors' => $errors,
                    'total_rows' => count($rows),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ]);
            }

            DB::commit();

            return $this->success([
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ], "Import completed. {$successCount} students imported successfully, {$errorCount} errors.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to process Excel file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export class students to Excel
     */
    public function exportClassStudents(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
        ]);

        $schoolId = $request->get('school_id');
        $classId = $request->get('class_id');

        // Verify class belongs to school
        $class = ClassModel::where('id', $classId)
            ->where('school_id', $schoolId)
            ->with('academicYear')
            ->first();
        
        if (!$class) {
            abort(404, 'Class not found');
        }

        $students = $class->students()->with('parents.user')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'Class: ' . $class->name);
        $sheet->setCellValue('A2', 'Academic Year: ' . ($class->academicYear->name ?? 'N/A'));
        $sheet->setCellValue('A3', 'Export Date: ' . now()->format('Y-m-d H:i:s'));

        // Set headers
        $headers = [
            'A5' => 'Student Number',
            'B5' => 'First Name',
            'C5' => 'Last Name',
            'D5' => 'Middle Name',
            'E5' => 'Date of Birth',
            'F5' => 'Gender',
            'G5' => 'Phone',
            'H5' => 'Email',
            'I5' => 'Address',
            'J5' => 'Guardians',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $sheet->getStyle('A5:J5')->getFont()->setBold(true);
        $sheet->getStyle('A5:J5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Set column widths
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }

        // Add student data
        $row = 6;
        foreach ($students as $student) {
            $sheet->setCellValue('A' . $row, $student->student_number);
            $sheet->setCellValue('B' . $row, $student->first_name);
            $sheet->setCellValue('C' . $row, $student->last_name);
            $sheet->setCellValue('D' . $row, $student->middle_name ?? '');
            $sheet->setCellValue('E' . $row, $student->date_of_birth?->format('Y-m-d') ?? '');
            $sheet->setCellValue('F' . $row, $student->gender ?? '');
            $sheet->setCellValue('G' . $row, $student->phone ?? '');
            $sheet->setCellValue('H' . $row, $student->email ?? '');
            $sheet->setCellValue('I' . $row, $student->address ?? '');
            
            // Guardians
            $guardians = $student->parents->map(function ($parent) {
                if ($parent->user) {
                    return $parent->user->first_name . ' ' . $parent->user->last_name;
                }
                return '';
            })->filter()->implode(', ');
            $sheet->setCellValue('J' . $row, $guardians);
            
            $row++;
        }

        $sheet->freezePane('A6');

        $writer = new Xlsx($spreadsheet);
        $filename = 'class_' . $class->name . '_students_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

