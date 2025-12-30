New Fee Payment Received

Dear Admin,

A new fee payment has been received and verified:

Amount: {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
Payment Reference: {{ $payment->reference }}
Student: {{ $payment->student->full_name ?? ($payment->student->first_name . ' ' . $payment->student->last_name) }}
Student Number: {{ $payment->student->student_number ?? 'N/A' }}
Term: {{ $payment->term->name ?? 'N/A' }}
Parent/Guardian: {{ $payment->parent->user->first_name ?? '' }} {{ $payment->parent->user->last_name ?? '' }}
Payment Method: {{ ucfirst($payment->payment_method ?? 'N/A') }}
@if($payment->payment_reference)
Payment Reference: {{ $payment->payment_reference }}
@endif
Verified At: {{ $payment->verified_at ? $payment->verified_at->format('Y-m-d H:i:s') : 'N/A' }}
Verified By: {{ ucfirst(str_replace('_', ' ', $payment->verified_by ?? 'N/A')) }}

Please ensure the payment is properly recorded in your accounting system.

Best regards,
School Management System

