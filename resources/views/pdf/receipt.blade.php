<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #204654; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1f7e91; padding-bottom: 20px; margin-bottom: 30px; }
        .brand { font-size: 22px; font-weight: bold; color: #1f7e91; }
        .tagline { font-size: 11px; color: #5C7688; margin-top: 4px; }
        .receipt-meta { text-align: right; font-size: 12px; color: #5C7688; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-top: 6px; background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; color: #5C7688; padding: 8px 0; border-bottom: 1px solid #d5f3f6; }
        td { padding: 10px 0; border-bottom: 1px solid #eefbfc; font-size: 13px; }
        .amount-row td { font-size: 18px; font-weight: bold; color: #1f7e91; border-bottom: none; padding-top: 16px; }
        .section-title { font-size: 12px; text-transform: uppercase; color: #5C7688; margin-top: 30px; margin-bottom: 8px; }
        .footer { margin-top: 50px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">Aquameter</div>
            <div class="tagline">Water Utility Customer Service System</div>
        </div>
        <div class="receipt-meta">
            <div>Receipt #{{ $payment->id }}</div>
            <div>Paid {{ $payment->paid_at->toFormattedDateString() }}</div>
            <div class="status">PAID</div>
        </div>
    </div>

    <div class="section-title">Received From</div>
    <div>{{ $payment->account->user->name }}</div>
    <div>{{ $payment->account->address }}, {{ $payment->account->zone }}</div>
    <div>Account: {{ $payment->account->account_number }}</div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Bill #{{ $payment->bill_id }} ({{ $payment->bill->units_consumed }} units consumed)</td>
                <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                <td>{{ $payment->reference ?: '—' }}</td>
                <td>KES {{ $payment->amount }}</td>
            </tr>
            <tr class="amount-row">
                <td colspan="3">Total Paid</td>
                <td>KES {{ $payment->amount }}</td>
            </tr>
        </tbody>
    </table>

    @if($payment->recordedBy)
    <div class="section-title">Recorded By</div>
    <div>{{ $payment->recordedBy->name }}</div>
    @endif

    <div class="footer">
        This is a system-generated payment receipt from Aquameter. For questions, contact your utility provider.
    </div>
</body>
</html>
