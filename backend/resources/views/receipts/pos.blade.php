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
            font-size: 8px;
            line-height: 1.15;
            color: #000;
            width: 80mm;
            max-width: 80mm;
            padding: 2mm;
            background: white;
            margin: 0;
        }
        
        .receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }
        
        .school-name {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 1px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        
        .school-address {
            font-size: 7px;
            margin-bottom: 1px;
            line-height: 1.2;
        }
        
        .school-contact {
            font-size: 7px;
            line-height: 1.2;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin: 4px 0;
            text-transform: uppercase;
        }
        
        .receipt-info {
            margin: 3px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-size: 7px;
            line-height: 1.2;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .info-value {
            text-align: right;
            word-break: break-word;
        }
        
        .payment-details {
            margin: 3px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-size: 7px;
            line-height: 1.2;
        }
        
        .detail-label {
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .detail-value {
            text-align: right;
            word-break: break-word;
            margin-left: 4px;
        }
        
        .amount-section {
            text-align: center;
            margin: 4px 0;
            padding: 4px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .amount-label {
            font-size: 7px;
            margin-bottom: 2px;
        }
        
        .amount-value {
            font-size: 14px;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 4px;
            padding-top: 3px;
            border-top: 1px dashed #000;
            font-size: 6px;
            line-height: 1.2;
        }
        
        .thank-you {
            text-align: center;
            margin: 4px 0;
            font-size: 8px;
            font-weight: bold;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 2px 0;
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
                <span class="detail-value">{{ $payment->term->academicYear->name ?? 'N/A' }}</span>
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
            <div style="margin-top: 2px;">This is a computer-generated receipt</div>
            <div style="margin-top: 2px;">Keep this receipt for your records</div>
        </div>
    </div>
</body>
</html>

