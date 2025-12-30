<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            width: 80mm;
            max-width: 80mm;
            padding: 5mm;
            background: white;
        }
        
        .receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }
        
        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .school-address {
            font-size: 9px;
            margin-bottom: 2px;
        }
        
        .school-contact {
            font-size: 9px;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        
        .receipt-info {
            margin: 8px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 6px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 9px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .info-value {
            text-align: right;
        }
        
        .payment-details {
            margin: 8px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 9px;
        }
        
        .detail-label {
            font-weight: bold;
        }
        
        .detail-value {
            text-align: right;
        }
        
        .amount-section {
            text-align: center;
            margin: 10px 0;
            padding: 8px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .amount-label {
            font-size: 9px;
            margin-bottom: 4px;
        }
        
        .amount-value {
            font-size: 18px;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 8px;
        }
        
        .thank-you {
            text-align: center;
            margin: 10px 0;
            font-size: 10px;
            font-weight: bold;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- School Header -->
        <div class="header">
            @if($school)
                <div class="school-name">{{ $school->name }}</div>
                @if($school->address)
                    <div class="school-address">{{ $school->address }}</div>
                @endif
                @if($school->phone || $school->email)
                    <div class="school-contact">
                        @if($school->phone)Tel: {{ $school->phone }}@endif
                        @if($school->phone && $school->email) | @endif
                        @if($school->email)Email: {{ $school->email }}@endif
                    </div>
                @endif
            @else
                <div class="school-name">School Management System</div>
            @endif
        </div>
        
        <!-- Receipt Title -->
        <div class="receipt-title">Payment Receipt</div>
        
        <!-- Receipt Information -->
        <div class="receipt-info">
            <div class="info-row">
                <span class="info-label">Receipt No:</span>
                <span class="info-value">{{ $payment->reference }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $payment->verified_at ? $payment->verified_at->format('d/m/Y H:i') : $payment->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        
        <!-- Payment Details -->
        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Student:</span>
                <span class="detail-value">{{ $payment->student->full_name ?? ($payment->student->first_name . ' ' . $payment->student->last_name) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Student No:</span>
                <span class="detail-value">{{ $payment->student->student_number ?? 'N/A' }}</span>
            </div>
            <div class="divider"></div>
            <div class="detail-row">
                <span class="detail-label">Term:</span>
                <span class="detail-value">{{ $payment->term->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Academic Year:</span>
                <span class="detail-value">{{ $payment->term->academic_year->name ?? 'N/A' }}</span>
            </div>
            <div class="divider"></div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">{{ strtoupper($payment->payment_method ?? 'N/A') }}</span>
            </div>
            @if($payment->payment_reference)
            <div class="detail-row">
                <span class="detail-label">Ref No:</span>
                <span class="detail-value">{{ $payment->payment_reference }}</span>
            </div>
            @endif
        </div>
        
        <!-- Amount Section -->
        <div class="amount-section">
            <div class="amount-label">Amount Paid</div>
            <div class="amount-value">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</div>
        </div>
        
        <!-- Thank You Message -->
        <div class="thank-you">
            Thank You!
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>Generated: {{ $generated_at->format('d/m/Y H:i:s') }}</div>
            <div style="margin-top: 4px;">This is a computer-generated receipt</div>
            <div style="margin-top: 4px;">Keep this receipt for your records</div>
        </div>
    </div>
</body>
</html>

