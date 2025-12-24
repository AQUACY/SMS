<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Sheet - {{ $class->name }}</title>
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
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .school-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .school-address {
            font-size: 9pt;
            margin-bottom: 3px;
        }
        
        .sheet-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
        }
        
        .info-section {
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .student-name {
            text-align: left;
            padding-left: 5px;
        }
        
        .status-present {
            color: #006400;
            font-weight: bold;
        }
        
        .status-absent {
            color: #dc143c;
            font-weight: bold;
        }
        
        .status-late {
            color: #ff8c00;
            font-weight: bold;
        }
        
        .status-excused {
            color: #4169e1;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 8pt;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .summary {
            margin-top: 15px;
            font-size: 9pt;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
    </style>
</head>
<body >
    <div class="header">
        <div class="school-name">{{ $school->name ?? 'SCHOOL NAME' }}</div>
        @if($school && $school->address)
        <div class="school-address">{{ $school->address }}</div>
        @endif
        @if($school && $school->phone)
        <div class="school-address">Tel: {{ $school->phone }}</div>
        @endif
        <div class="sheet-title">Attendance Sheet</div>
    </div>
    
    <div class="info-section">
        <div>
            <div class="info-item">
                <span class="info-label">Class:</span>
                <span>{{ $class->name }} ({{ $class->level }}{{ $class->section ? ' - ' . $class->section : '' }})</span>
            </div>
            <div class="info-item">
                <span class="info-label">Term:</span>
                <span>{{ $term->name }} ({{ $term->academicYear->name }})</span>
            </div>
            @if($class->classTeacher && $class->classTeacher->user)
            <div class="info-item">
                <span class="info-label">Class Teacher:</span>
                <span>{{ $class->classTeacher->user->first_name }} {{ $class->classTeacher->user->last_name }}</span>
            </div>
            @endif
        </div>
        <div>
            <div class="info-item">
                <span class="info-label">Date Range:</span>
                <span>{{ $date ?? $term->start_date->format('d/m/Y') . ' - ' . $term->end_date->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Generated:</span>
                <span>{{ $generated_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 150px; text-align: left;">Student Name</th>
                <th style="width: 80px;">Student No.</th>
                @php
                    $dates = $attendanceRecords->keys()->sort();
                    $dateCount = $dates->count();
                @endphp
                @foreach($dates as $date)
                <th style="width: 50px; font-size: 7pt;">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</th>
                @endforeach
                <th style="width: 60px;">Present</th>
                <th style="width: 60px;">Absent</th>
                <th style="width: 60px;">Late</th>
                <th style="width: 60px;">Excused</th>
                <th style="width: 70px;">Total Days</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            @php
                $studentAttendance = $attendanceRecords->map(function($records) use ($student) {
                    return $records->firstWhere('student_id', $student->id);
                });
                
                $present = $studentAttendance->where('status', 'present')->count();
                $absent = $studentAttendance->where('status', 'absent')->count();
                $late = $studentAttendance->where('status', 'late')->count();
                $excused = $studentAttendance->where('status', 'excused')->count();
                $totalDays = $studentAttendance->count();
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="student-name">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</td>
                <td>{{ $student->student_number }}</td>
                @foreach($dates as $date)
                @php
                    $record = $studentAttendance->get($date);
                @endphp
                <td>
                    @if($record)
                        @if($record->status === 'present')
                            <span class="status-present">✓</span>
                        @elseif($record->status === 'absent')
                            <span class="status-absent">✗</span>
                        @elseif($record->status === 'late')
                            <span class="status-late">L</span>
                        @elseif($record->status === 'excused')
                            <span class="status-excused">E</span>
                        @endif
                    @else
                        -
                    @endif
                </td>
                @endforeach
                <td>{{ $present }}</td>
                <td>{{ $absent }}</td>
                <td>{{ $late }}</td>
                <td>{{ $excused }}</td>
                <td><strong>{{ $totalDays }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="summary">
        <div class="summary-row">
            <strong>Total Students:</strong> <span>{{ $students->count() }}</span>
        </div>
        <div class="summary-row">
            <strong>Total Days Recorded:</strong> <span>{{ $dates->count() }}</span>
        </div>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div>Class Teacher's Signature</div>
            <div style="margin-top: 30px;">_____________________</div>
        </div>
        <div class="signature-box">
            <div>Head Teacher's Signature</div>
            <div style="margin-top: 30px;">_____________________</div>
        </div>
        <div class="signature-box">
            <div>Date</div>
            <div style="margin-top: 30px;">_____________________</div>
        </div>
    </div>
    
    <div class="footer" style="margin-top: 20px; font-size: 7pt; color: #666;">
        <div>Legend: ✓ = Present, ✗ = Absent, L = Late, E = Excused</div>
        <div>Generated by School Management System</div>
    </div>
</body>
</html>

