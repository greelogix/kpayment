<?php

namespace Greelogix\KPayment\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Greelogix\KPayment\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    /**
     * Display payment methods
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('sort_order')->orderBy('name')->get();
        return view('kpayment::admin.payment-methods.index', compact('paymentMethods'));
    }

    /**
     * Toggle payment method status
     */
    public function toggleStatus(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'field' => 'required|in:is_active,is_ios_enabled,is_android_enabled,is_web_enabled',
            'status' => 'required|boolean',
        ]);

        $paymentMethod->update([
            $request->field => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('kpayment.admin.payment_methods.status_updated_successfully'),
            'status' => $paymentMethod->{$request->field},
        ]);
    }
}

