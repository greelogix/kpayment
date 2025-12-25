<?php

namespace Greelogix\KPayment\Models;

use Illuminate\Database\Eloquent\Model;

class KnetPayment extends Model
{
    protected $table = 'kpayment_payments';

    protected $fillable = [
        'payment_id',
        'track_id',
        'result',
        'result_code',
        'auth',
        'ref',
        'trans_id',
        'post_date',
        'udf1',
        'udf2',
        'udf3',
        'udf4',
        'udf5',
        'amount',
        'currency',
        'payment_method',
        'status',
        'response_data',
        'request_data',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'response_data' => 'array',
        'request_data' => 'array',
    ];

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success' && 
               in_array($this->result_code, ['CAPTURED', 'SUCCESS']);
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed' || 
               !in_array($this->result_code, ['CAPTURED', 'SUCCESS']);
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope for successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success')
            ->whereIn('result_code', ['CAPTURED', 'SUCCESS']);
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'failed')
              ->orWhereNotIn('result_code', ['CAPTURED', 'SUCCESS']);
        });
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}


