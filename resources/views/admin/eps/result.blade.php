<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'পেমেন্ট ফলাফল' }} - Rootva</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Hind Siliguri', sans-serif;
        }
        body {
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            max-width: 420px;
            width: 100%;
            text-align: center;
            padding: 35px 25px;
            border: 1px solid #e2e8f0;
        }
        .icon-circle {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
        .icon-success {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .icon-failed {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .icon-cancelled {
            background-color: #fef3c7;
            color: #d97706;
        }
        h2 {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }
        p {
            font-size: 15px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 25px;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
        }
        .info-row:not(:last-child) {
            border-bottom: 1px dashed #cbd5e1;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
        }
        .info-value {
            color: #0f172a;
            font-weight: 700;
        }
        .btn-action {
            display: block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-success {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
        }
        .btn-retry {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }
        .btn-action:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .logo-footer {
            margin-top: 25px;
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="card">
        @if($status === 'success')
            <div class="icon-circle icon-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2>{{ $title ?? 'পেমেন্ট সফল হয়েছে!' }}</h2>
            <p>{{ $message }}</p>
        @elseif($status === 'cancelled')
            <div class="icon-circle icon-cancelled">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h2>{{ $title ?? 'পেমেন্ট বাতিল হয়েছে' }}</h2>
            <p>{{ $message }}</p>
        @else
            <div class="icon-circle icon-failed">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <h2>{{ $title ?? 'পেমেন্ট ব্যর্থ হয়েছে' }}</h2>
            <p>{{ $message }}</p>
        @endif

        @if($txn)
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">ট্রান্সেকশন আইডি</span>
                <span class="info-value font-monospace">{{ $txn->merchant_transaction_id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">টাকার পরিমাণ</span>
                <span class="info-value text-success font-monospace">৳{{ number_format($txn->amount, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">পেমেন্ট গেটওয়ে</span>
                <span class="info-value">{{ $txn->financial_entity ?: 'EPS Gateway' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">পেমেন্টের উদ্দেশ্য</span>
                <span class="info-value">
                    @if($txn->purpose === 'verification')
                        অ্যাকাউন্ট ভেরিফিকেশন
                    @elseif($txn->purpose === 'add_balance')
                        ব্যালেন্স ডিপোজিট
                    @else
                        অর্ডার পেমেন্ট
                    @endif
                </span>
            </div>
        </div>
        @endif

        <button onclick="returnToApp()" class="btn-action {{ $status === 'success' ? 'btn-success' : 'btn-retry' }}">
            <i class="fa-solid fa-arrow-left me-2"></i> অ্যাপে ফিরে যান
        </button>

        <div class="logo-footer">
            <i class="fa-solid fa-shield-halved me-1 text-primary"></i> Rootva Secure Payment &bull; Auto Payment
        </div>
    </div>

    <script>
        function returnToApp() {
            window.location.href = "rootva://payment/done?status={{ $status }}";
            setTimeout(function() {
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.close();
                }
            }, 300);
        }
    </script>
</body>
</html>
