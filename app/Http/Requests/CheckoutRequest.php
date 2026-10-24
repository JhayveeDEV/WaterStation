<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'address_id' => ['required_if:delivery_method,delivery', 'nullable', 'exists:addresses,id'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'schedule_date' => ['required', 'date', 'after_or_equal:today'],
            'schedule_slot' => ['nullable', 'string'],
            'payment_method' => ['required', Rule::in(['cash', 'qrph'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'checkout_token' => ['required', 'string', 'max:64'],
        ];
    }

    /** Same-day orders stop being accepted past a cutoff time (default 4:00 PM), configurable via the settings table. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $date = $this->input('schedule_date');

            if (! $date || $date !== now()->toDateString()) {
                return;
            }

            $cutoff = Setting::get('same_day_cutoff_time', '16:00');
            $cutoffAt = Carbon::parse(now()->toDateString().' '.$cutoff);

            if (now()->greaterThan($cutoffAt)) {
                $validator->errors()->add(
                    'schedule_date',
                    'Same-day orders are no longer accepted after '.$cutoffAt->format('g:i A').'. Please choose a later date.'
                );
            }
        });
    }
}
