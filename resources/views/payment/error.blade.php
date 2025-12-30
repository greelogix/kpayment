<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('kpay.payment.error.title') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @if(app()->getLocale() === 'ar')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @endif
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .error-card {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </div>
            <h1 class="mb-3">{{ __('kpay.payment.error.heading') }}</h1>
            <p class="text-muted mb-4">
                @if(session('error'))
                    {{ session('error') }}
                @elseif(session('message'))
                    {{ session('message') }}
                @else
                    {{ __('kpay.payment.error.default_message') }}
                @endif
            </p>
            
            @if(session('payment'))
                @php
                    $payment = session('payment');
                @endphp
                <div class="card bg-light mb-4">
                    <div class="card-body {{ app()->getLocale() === 'ar' ? 'text-end' : 'text-start' }}">
                        <h6 class="card-title">{{ __('kpay.payment.error.details') }}</h6>
                        <hr>
                        @if($payment->track_id)
                            <p class="mb-2"><strong>{{ __('kpay.payment.error.order_id') }}:</strong> {{ $payment->track_id }}</p>
                        @endif
                        @if($payment->trans_id)
                            <p class="mb-2"><strong>{{ __('kpay.payment.error.transaction_id') }}:</strong> {{ $payment->trans_id }}</p>
                        @endif
                        @if($payment->result)
                            <p class="mb-2"><strong>{{ __('kpay.payment.error.result') }}:</strong> <span class="text-danger">{{ $payment->result }}</span></p>
                        @endif
                        @if($payment->amount)
                            <p class="mb-0"><strong>{{ __('kpay.payment.error.amount') }}:</strong> {{ number_format($payment->amount, 3) }} {{ $payment->currency ?? __('kpay.common.currency.kwd') }}</p>
                        @endif
                    </div>
                </div>
            @endif
            
            <div class="d-grid gap-2">
                <a href="{{ url('/') }}" class="btn btn-primary btn-lg">{{ __('kpay.payment.error.return_home') }}</a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">{{ __('kpay.payment.error.try_again') }}</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

