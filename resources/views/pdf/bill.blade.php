<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #204654; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1f7e91; padding-bottom: 20px; margin-bottom: 30px; }
        .brand { font-size: 22px; font-weight: bold; color: #1f7e91; }
        .tagline { font-size: 11px; color: #5C7688; margin-top: 4px; }
        .bill-meta { text-align: right; font-size: 12px; color: #5C7688; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-top: 6px; }
        .status.paid { background: #d1fae5; color: #065f46; }
        .status.pending { background: #d5f3f6; color: #1f6676; }
        .status.overdue { background: #fef3c7; color: #92400e; }
        .status.defaulted { background: #fee2e2; color: #991b1b; }
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
        <div class="bill-meta">
            <div>Bill #{{ $bill->id }}</div>
            <div>Issued {{ $bill->created_at->toFormattedDateString() }}</div>
            <div>Due {{ $bill->due_date->toFormattedDateString() }}</div>
            <div class="status {{ $bill->status->value }}">{{ $bill->status->label() }}</div>
        </div>
    </div>

    <div class="section-title">Billed To</div>
    <div>{{ $bill->account->user->name }}</div>
    <div>{{ $bill->account->address }}, {{ $bill->account->zone }}</div>
    <div>Account: {{ $bill->account->account_number }}</div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Previous Reading</th>
                <th>Current Reading</th>
                <th>Units Consumed</th>
                <th>Rate</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Water consumption</td>
                <td>{{ $bill->previous_reading_value }}</td>
                <td>{{ $bill->current_reading_value }}</td>
                <td>{{ $bill->units_consumed }}</td>
                <td>KES {{ $bill->rate_applied }} / unit</td>
            </tr>
            <tr class="amount-row">
                <td colspan="4">Total Amount</td>
                <td>KES {{ $bill->amount }}</td>
            </tr>
        </tbody>
    </table>

    @if($bill->payment)
    <div class="section-title">Payment Record</div>
    <div>Paid via {{ ucfirst(str_replace('_', ' ', $bill->payment->method)) }} on {{ $bill->payment->paid_at->toFormattedDateString() }}</div>
    @if($bill->payment->reference)
    <div>Reference: {{ $bill->payment->reference }}</div>
    @endif
    @endif

    <div class="footer">
        This is a system-generated bill from Aquameter. For questions, contact your utility provider.
    </div>
</body>
</html>
