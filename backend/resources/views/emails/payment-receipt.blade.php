<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }
        .receipt-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #9c27b0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #9c27b0;
            text-align: center;
            padding: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Payment Receipt</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $payment->parent->user->first_name ?? 'Parent' }},</p>
        
        <p>Thank you for your payment. Please find your receipt attached to this email.</p>
        
        <div class="receipt-info">
            <div class="amount">
                {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Receipt Number:</span>
                <span class="detail-value">{{ $payment->reference }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Student:</span>
                <span class="detail-value">{{ $payment->student->full_name ?? ($payment->student->first_name . ' ' . $payment->student->last_name) }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Term:</span>
                <span class="detail-value">{{ $payment->term->name ?? 'N/A' }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Payment Date:</span>
                <span class="detail-value">{{ $payment->verified_at ? $payment->verified_at->format('F d, Y') : 'N/A' }}</span>
            </div>
        </div>
        
        <p>Please keep this receipt for your records.</p>
        
        <p>Best regards,<br>School Management System</p>
    </div>
    
    <div class="footer">
        <p>This receipt is also available in your payment history.</p>
    </div>
</body>
</html>

