<?php

namespace App\Http\Controllers\Api;

use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReportCardController extends BaseApiController
{
    /**
     * Display a listing of report cards
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->get('school_id');
        
        // Get all students with results, grouped by student and term
        $query = Result::whereHas('assessment', function ($q) use ($schoolId) {
            $q->whereHas('term', function ($query) use ($schoolId) {
                $query->whereHas('academicYear', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            });
        })
        ->with(['student', 'assessment.term.academicYear'])
        ->get()
        ->groupBy(['student_id', function ($result) {
            return $result->assessment->term_id;
        }]);

        // Format the data for frontend
        $reportCards = [];
        foreach ($query as $studentId => $terms) {
            $student = Student::find($studentId);
            if (!$student || $student->school_id !== $schoolId) {
                continue;
            }

            foreach ($terms as $termId => $results) {
                $term = Term::with('academicYear')->find($termId);
                if (!$term || $term->academicYear->school_id !== $schoolId) {
                    continue;
                }

                $statistics = $this->calculateStatistics($results);
                
                $reportCards[] = [
                    'student_id' => $student->id,
                    'student' => [
                        'id' => $student->id,
                        'student_number' => $student->student_number,
                        'first_name' => $student->first_name,
                        'middle_name' => $student->middle_name,
                        'last_name' => $student->last_name,
                        'full_name' => $student->full_name,
                    ],
                    'term_id' => $term->id,
                    'term' => [
                        'id' => $term->id,
                        'name' => $term->name,
                        'term_number' => $term->term_number,
                        'start_date' => $term->start_date?->format('Y-m-d'),
                        'end_date' => $term->end_date?->format('Y-m-d'),
                        'status' => $term->status,
                        'academic_year' => [
                            'id' => $term->academicYear->id,
                            'name' => $term->academicYear->name,
                        ],
                    ],
                    'statistics' => $statistics,
                    'results_count' => $results->count(),
                ];
            }
        }

        // Apply filters
        if ($request->has('student_id')) {
            $reportCards = array_filter($reportCards, function ($card) use ($request) {
                return $card['student_id'] == $request->get('student_id');
            });
        }

        if ($request->has('term_id')) {
            $reportCards = array_filter($reportCards, function ($card) use ($request) {
                return $card['term_id'] == $request->get('term_id');
            });
        }

        // Sort by student name, then by term
        usort($reportCards, function ($a, $b) {
            $nameCompare = strcmp($a['student']['full_name'], $b['student']['full_name']);
            if ($nameCompare !== 0) {
                return $nameCompare;
            }
            return $a['term']['term_number'] <=> $b['term']['term_number'];
        });

        return $this->success(array_values($reportCards), 'Report cards retrieved successfully');
    }

    /**
     * Generate report card
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $student = Student::findOrFail($request->get('student_id'));
        $term = Term::with('academicYear')->findOrFail($request->get('term_id'));

        // Ensure student and term belong to user's school
        $schoolId = $request->get('school_id');
        if ($student->school_id !== $schoolId || $term->academicYear->school_id !== $schoolId) {
            return $this->error('Student or term not found', 404);
        }

        // Get all results for the term
        $results = $student->results()
            ->whereHas('assessment', function ($q) use ($term) {
                $q->where('term_id', $term->id);
            })
            ->with(['assessment.classSubject.subject', 'assessment.classSubject.class'])
            ->get();

        // Format results for response
        $formattedResults = $results->map(function ($result) {
            return [
                'id' => $result->id,
                'assessment_id' => $result->assessment_id,
                'marks_obtained' => $result->marks_obtained,
                'grade' => $result->grade,
                'remarks' => $result->remarks,
                'assessment' => [
                    'id' => $result->assessment->id,
                    'name' => $result->assessment->name,
                    'type' => $result->assessment->type,
                    'total_marks' => $result->assessment->total_marks,
                    'weight' => $result->assessment->weight,
                    'subject' => [
                        'id' => $result->assessment->classSubject->subject->id,
                        'name' => $result->assessment->classSubject->subject->name,
                        'code' => $result->assessment->classSubject->subject->code,
                    ],
                    'class' => [
                        'id' => $result->assessment->classSubject->class->id,
                        'name' => $result->assessment->classSubject->class->name,
                    ],
                ],
            ];
        });

        // Calculate totals and averages
        $reportCard = [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
                'gender' => $student->gender,
            ],
            'term' => [
                'id' => $term->id,
                'name' => $term->name,
                'term_number' => $term->term_number,
                'start_date' => $term->start_date?->format('Y-m-d'),
                'end_date' => $term->end_date?->format('Y-m-d'),
                'academic_year' => [
                    'id' => $term->academicYear->id,
                    'name' => $term->academicYear->name,
                ],
            ],
            'results' => $formattedResults,
            'statistics' => $this->calculateStatistics($results),
        ];

        return $this->success($reportCard, 'Report card generated successfully');
    }

    /**
     * Display the specified report card
     */
    public function show(Request $request, $studentId, $termId): JsonResponse
    {
        $student = Student::findOrFail($studentId);
        $term = Term::with('academicYear')->findOrFail($termId);

        // Check subscription if parent
        if (auth()->user()->isParent()) {
            if (!auth()->user()->parent->hasActiveSubscription($studentId, $termId)) {
                return $this->error('Subscription required to view report card for this term', 403);
            }
        }

        // Ensure student and term belong to user's school
        $schoolId = $request->get('school_id');
        if ($student->school_id !== $schoolId || $term->academicYear->school_id !== $schoolId) {
            return $this->error('Student or term not found', 404);
        }

        // Get all results for the term
        $results = $student->results()
            ->whereHas('assessment', function ($q) use ($term) {
                $q->where('term_id', $term->id);
            })
            ->with(['assessment.classSubject.subject', 'assessment.classSubject.class'])
            ->get();

        // Format results for response
        $formattedResults = $results->map(function ($result) {
            return [
                'id' => $result->id,
                'assessment_id' => $result->assessment_id,
                'marks_obtained' => $result->marks_obtained,
                'grade' => $result->grade,
                'remarks' => $result->remarks,
                'assessment' => [
                    'id' => $result->assessment->id,
                    'name' => $result->assessment->name,
                    'type' => $result->assessment->type,
                    'total_marks' => $result->assessment->total_marks,
                    'weight' => $result->assessment->weight,
                    'subject' => [
                        'id' => $result->assessment->classSubject->subject->id,
                        'name' => $result->assessment->classSubject->subject->name,
                        'code' => $result->assessment->classSubject->subject->code,
                    ],
                    'class' => [
                        'id' => $result->assessment->classSubject->class->id,
                        'name' => $result->assessment->classSubject->class->name,
                    ],
                ],
            ];
        });

        $reportCard = [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
                'gender' => $student->gender,
            ],
            'term' => [
                'id' => $term->id,
                'name' => $term->name,
                'term_number' => $term->term_number,
                'start_date' => $term->start_date?->format('Y-m-d'),
                'end_date' => $term->end_date?->format('Y-m-d'),
                'academic_year' => [
                    'id' => $term->academicYear->id,
                    'name' => $term->academicYear->name,
                ],
            ],
            'results' => $formattedResults,
            'statistics' => $this->calculateStatistics($results),
        ];

        return $this->success($reportCard, 'Report card retrieved successfully');
    }

    /**
     * Generate PDF for report card
     */
    public function generatePdf(Request $request)
    {
        try {
            // If token is provided as query parameter (for preview), authenticate with it
            if ($request->has('token')) {
                try {
                    $token = $request->get('token');
                    $user = JWTAuth::setToken($token)->authenticate();
                    if (!$user) {
                        return $this->error('Invalid token', 401);
                    }
                    auth('api')->setUser($user);
                    if ($user->school_id) {
                        $request->merge(['school_id' => $user->school_id]);
                    }
                } catch (\Exception $e) {
                    return $this->error('Invalid token', 401);
                }
            }

            $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'term_id' => ['required', 'exists:terms,id'],
            ]);

            $student = Student::with('school')->findOrFail($request->get('student_id'));
            $term = Term::with('academicYear')->findOrFail($request->get('term_id'));

            // Ensure student and term belong to user's school
            $schoolId = $request->get('school_id');
            if (!$schoolId) {
                return $this->error('School ID is required', 400);
            }

            if ($student->school_id !== $schoolId || $term->academicYear->school_id !== $schoolId) {
                return $this->error('Student or term not found', 404);
            }

            // Get all results for the term
            $results = $student->results()
                ->whereHas('assessment', function ($q) use ($term) {
                    $q->where('term_id', $term->id);
                })
                ->with(['assessment.classSubject.subject', 'assessment.classSubject.class'])
                ->get()
                ->sortBy(function ($result) {
                    return $result->assessment->classSubject->subject->name ?? '';
                })
                ->values();

            $statistics = $this->calculateStatistics($results);

            // Get school information
            $school = School::find($schoolId);
            if (!$school) {
                return $this->error('School not found', 404);
            }

            // Check if there are any results
            if ($results->isEmpty()) {
                return $this->error('No results found for this student and term', 404);
            }

            // Prepare data for PDF
            $data = [
                'school' => $school,
                'student' => $student,
                'term' => $term,
                'results' => $results,
                'statistics' => $statistics,
                'generated_at' => now(),
            ];

            // Check if view exists
            if (!view()->exists('report-cards.card')) {
                return $this->error('PDF template not found', 500);
            }

            // Generate PDF
            try {
                $pdf = Pdf::loadView('report-cards.card', $data);
                $pdf->setPaper('A4', 'portrait');
            } catch (\Exception $e) {
                \Log::error('PDF View Rendering Error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->error('Failed to render PDF template: ' . $e->getMessage(), 500);
            }

            $action = $request->get('action', 'download'); // 'preview' or 'download'
            $filename = 'report_card_' . $student->student_number . '_' . $term->name . '.pdf';

            if ($action === 'preview') {
                return response($pdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                    ->header('Content-Transfer-Encoding', 'binary')
                    ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                    ->header('Pragma', 'public')
                    ->header('Accept-Ranges', 'bytes');
            }

            return $pdf->download($filename);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed: ' . $e->getMessage(), 422);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return $this->error('Failed to generate PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate report card statistics
     */
    private function calculateStatistics($results): array
    {
        $totalMarks = 0;
        $obtainedMarks = 0;
        $subjectCount = 0;

        foreach ($results as $result) {
            $totalMarks += $result->assessment->total_marks;
            $obtainedMarks += $result->marks_obtained;
            $subjectCount++;
        }

        $average = $subjectCount > 0 ? ($obtainedMarks / $totalMarks) * 100 : 0;

        return [
            'total_subjects' => $subjectCount,
            'total_marks' => $totalMarks,
            'obtained_marks' => $obtainedMarks,
            'average_percentage' => round($average, 2),
            'grade' => $this->calculateGrade($average),
        ];
    }

    /**
     * Calculate grade using school's grading scale
     */
    private function calculateGrade($percentage): string
    {
        // Get the school's default grading scale
        $schoolId = request()->get('school_id');
        if ($schoolId) {
            $gradingScale = \App\Models\GradingScale::getDefaultForSchool($schoolId);
            if ($gradingScale) {
                return $gradingScale->getGradeForPercentage($percentage);
            }
        }

        // Fallback to default grading if no scale is configured
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        if ($percentage >= 40) return 'E';
        return 'F';
    }
}

