<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - {{ $student->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            color: #000;
            margin: 20px !important;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        
        .school-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .school-address {
            font-size: 9pt;
            margin-bottom: 3px;
        }
        
        .report-card-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .student-info {
            margin: 20px 0;
            border: 1px solid #000;
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 10pt;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex: 1;
        }
        
        .results-section {
            margin: 20px 0;
        }
        
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }
        
        th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
        }
        
        td {
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .summary-section {
            margin: 20px 0;
            border: 2px solid #000;
            padding: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 10pt;
            padding: 5px 0;
            border-bottom: 1px solid #ccc;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }
        
        .summary-label {
            font-weight: bold;
        }
        
        .summary-value {
            text-align: right;
        }
        
        .grade-badge {
            display: inline-block;
            padding: 3px 10px;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 11pt;
        }
        
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        .generated-info {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school->name ?? 'School Name' }}</div>
        @if($school && $school->address)
        <div class="school-address">{{ $school->address }}</div>
        @endif
        @if($school && $school->phone)
        <div class="school-address">Tel: {{ $school->phone }}</div>
        @endif
        @if($school && $school->email)
        <div class="school-address">Email: {{ $school->email }}</div>
        @endif
        <div class="report-card-title">Report Card</div>
    </div>

    <div class="student-info">
        <div class="info-row">
            <div class="info-label">Student Name:</div>
            <div class="info-value"><strong>{{ $student->full_name }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Student Number:</div>
            <div class="info-value">{{ $student->student_number }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Academic Year:</div>
            <div class="info-value">{{ $term->academicYear->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Term:</div>
            <div class="info-value">{{ $term->name }}</div>
        </div>
        @if($student->date_of_birth)
        <div class="info-row">
            <div class="info-label">Date of Birth:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($student->date_of_birth)->format('F d, Y') }}</div>
        </div>
        @endif
        @if($student->gender)
        <div class="info-row">
            <div class="info-label">Gender:</div>
            <div class="info-value">{{ ucfirst($student->gender) }}</div>
        </div>
        @endif
    </div>

    <div class="results-section">
        <div class="section-title">Academic Performance</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 25%;" class="text-left">Subject</th>
                    <th style="width: 20%;">Assessment</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 10%;">Marks Obtained</th>
                    <th style="width: 10%;">Total Marks</th>
                    <th style="width: 10%;">Percentage</th>
                    <th style="width: 10%;">Grade</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subjectGroups = $results->groupBy(function($result) {
                        return $result->assessment->classSubject->subject->id;
                    });
                    $rowNumber = 1;
                @endphp
                @foreach($subjectGroups as $subjectId => $subjectResults)
                    @php
                        $subject = $subjectResults->first()->assessment->classSubject->subject;
                        $subjectTotalMarks = 0;
                        $subjectObtainedMarks = 0;
                    @endphp
                    @foreach($subjectResults as $result)
                        @php
                            $subjectTotalMarks += $result->assessment->total_marks;
                            $subjectObtainedMarks += $result->marks_obtained;
                        @endphp
                        <tr>
                            <td>{{ $rowNumber }}</td>
                            <td class="text-left">
                                @if($loop->first)
                                    <strong>{{ $subject->name }}</strong>
                                    @if($subject->code)
                                        <br><small>({{ $subject->code }})</small>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $result->assessment->name }}</td>
                            <td>{{ ucfirst($result->assessment->type) }}</td>
                            <td>{{ number_format($result->marks_obtained, 2) }}</td>
                            <td>{{ number_format($result->assessment->total_marks, 2) }}</td>
                            <td>{{ number_format(($result->marks_obtained / $result->assessment->total_marks) * 100, 1) }}%</td>
                            <td><span class="grade-badge">{{ $result->grade ?? '-' }}</span></td>
                        </tr>
                        @php $rowNumber++; @endphp
                    @endforeach
                    @if($subjectResults->count() > 1)
                        @php
                            $subjectPercentage = $subjectTotalMarks > 0 ? ($subjectObtainedMarks / $subjectTotalMarks) * 100 : 0;
                            $schoolId = $school->id ?? ($student->school_id ?? null);
                            $gradingScale = $schoolId ? \App\Models\GradingScale::getDefaultForSchool($schoolId) : null;
                            $subjectGrade = $gradingScale ? $gradingScale->getGradeForPercentage($subjectPercentage) : '-';
                        @endphp
                        <tr style="background-color: #f9f9f9; font-weight: bold;">
                            <td colspan="4" class="text-left">Subject Total ({{ $subject->name }})</td>
                            <td>{{ number_format($subjectObtainedMarks, 2) }}</td>
                            <td>{{ number_format($subjectTotalMarks, 2) }}</td>
                            <td>{{ number_format($subjectPercentage, 1) }}%</td>
                            <td><span class="grade-badge">{{ $subjectGrade }}</span></td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="summary-section">
        <div class="section-title">Summary</div>
        <div class="summary-row">
            <div class="summary-label">Total Subjects:</div>
            <div class="summary-value">{{ $statistics['total_subjects'] }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Marks Obtained:</div>
            <div class="summary-value">{{ number_format($statistics['obtained_marks'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Marks Possible:</div>
            <div class="summary-value">{{ number_format($statistics['total_marks'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Average Percentage:</div>
            <div class="summary-value">{{ number_format($statistics['average_percentage'], 2) }}%</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Overall Grade:</div>
            <div class="summary-value">
                <span class="grade-badge" style="font-size: 14pt; padding: 5px 15px;">{{ $statistics['grade'] }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Class Teacher</strong>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Head Teacher</strong>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Date</strong>
            </div>
        </div>
    </div>

    <div class="generated-info">
        Generated on {{ $generated_at->format('F d, Y \a\t h:i A') }}
    </div>
</body>
</html>

