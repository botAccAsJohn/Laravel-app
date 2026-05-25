<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Failed Jobs Digest</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h2 { color: #e11d48; margin-top: 0; font-size: 24px; font-weight: 700; border-bottom: 2px solid #fda4af; padding-bottom: 10px; }
        .meta { margin-bottom: 25px; padding: 12px 16px; background-color: #f1f5f9; border-radius: 6px; font-size: 14px; color: #475569; }
        .table-container { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; font-weight: bold; color: #1e293b; font-size: 13px; text-transform: uppercase; }
        td { font-size: 14px; }
        .code { font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; color: #0f172a; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 12px; font-weight: bold; border-radius: 12px; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .footer { margin-top: 30px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Daily Failed Jobs Report</h2>
        
        <div class="meta">
            <strong>Period:</strong> Since {{ $data['since'] }}<br>
            <strong>Total Failures:</strong> <span class="badge badge-danger">{{ $data['total'] }}</span>
        </div>

        @if($data['total'] > 0)
            <div class="table-container">
                <h3>Failed Jobs Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Job Class</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['by_class'] as $class => $count)
                            <tr>
                                <td><code class="code">{{ $class }}</code></td>
                                <td><strong>{{ $count }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <p style="font-size: 14px; margin-top: 20px;">
                To retry these failed jobs, execute the following command in your terminal:
                <br>
                <code class="code">{{ $data['retry_hint'] }}</code>
            </p>
        @else
            <p style="color: #15803d; font-weight: bold; font-size: 16px;">
                🎉 All systems green! No jobs failed in the last 24 hours.
            </p>
        @endif

        <div class="footer">
            Sent automatically by {{ config('app.name') }} System Monitor.<br>
            {{ now()->toDateTimeString() }}
        </div>
    </div>
</body>
</html>
