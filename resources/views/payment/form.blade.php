<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('kpay.payment.form.title') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .redirect-container {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .message {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .sub-message {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="spinner"></div>
        <div class="message">{{ __('kpay.payment.form.redirecting') }}</div>
        <div class="sub-message">{{ __('kpay.payment.form.please_wait') }}</div>
    </div>

    <form id="knetForm" method="POST" action="{{ $formUrl }}" style="display: none;">
        @foreach($formData as $key => $value)
            @if($key !== 'payment_id') {{-- Exclude internal payment_id from form --}}
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
    </form>

    <script>
        // Auto-submit form immediately
        (function() {
            document.getElementById('knetForm').submit();
        })();
    </script>
</body>
</html>

