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
     * Store a new payment method
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:kpayment_payment_methods,code',
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_ios_enabled' => 'boolean',
            'is_android_enabled' => 'boolean',
            'is_web_enabled' => 'boolean',
            'sort_order' => 'integer',
        ]);

        PaymentMethod::create($request->all());

        return redirect()->route('kpayment.admin.payment-methods.index')
            ->with('success', 'Payment method created successfully.');
    }

    /**
     * Update a payment method
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'code' => 'required|string|unique:kpayment_payment_methods,code,' . $paymentMethod->id,
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_ios_enabled' => 'boolean',
            'is_android_enabled' => 'boolean',
            'is_web_enabled' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $paymentMethod->update($request->all());

        return redirect()->route('kpayment.admin.payment-methods.index')
            ->with('success', 'Payment method updated successfully.');
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
            'message' => 'Status updated successfully.',
            'status' => $paymentMethod->{$request->field},
        ]);
    }

    /**
     * Seed default payment methods
     */
    public function seed()
    {
        \Artisan::call('db:seed', [
            '--class' => 'Greelogix\KPayment\Database\Seeders\PaymentMethodSeeder'
        ]);

        return redirect()->route('kpayment.admin.payment-methods.index')
            ->with('success', 'Default payment methods seeded successfully.');
    }

    /**
     * Delete a payment method
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->route('kpayment.admin.payment-methods.index')
            ->with('success', 'Payment method deleted successfully.');
    }
}

