Payment Receipt

Dear {{ $payment->parent->user->first_name ?? 'Parent' }},

Thank you for your payment. Please find your receipt attached to this email.

Amount: {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
Receipt Number: {{ $payment->reference }}
Student: {{ $payment->student->full_name ?? ($payment->student->first_name . ' ' . $payment->student->last_name) }}
Term: {{ $payment->term->name ?? 'N/A' }}
Payment Date: {{ $payment->verified_at ? $payment->verified_at->format('F d, Y') : 'N/A' }}

Please keep this receipt for your records.

Best regards,
School Management System

