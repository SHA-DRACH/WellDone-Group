<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1 — what is needed
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('is_active', true)],
            'services' => ['required', 'array', 'min:1', 'max:20'],
            'services.*' => ['integer', Rule::exists('services', 'id')->where('is_active', true)->where('is_quotable', true)],

            // Step 2 — project detail
            'project_title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:30', 'max:8000'],
            'budget_range' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timeline' => ['nullable', 'string', 'max:120'],
            'preferred_start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'delivery_location' => ['nullable', 'string', 'max:200'],

            // Step 3 — who is asking
            'contact_name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            /*
             * `rfc` only, not `rfc,dns`. Submitting requires a verified
             * account, so deliverability has already been proven by the
             * confirmation link — a second DNS lookup adds latency and would
             * reject addresses that registration accepted, stranding the user.
             */
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],

            // Supporting documents — five files, 10 MB each
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', 'max:10240'],

            'consent' => ['accepted'],
            'website_url' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'services' => 'services required',
            'consent' => 'consent to process your data',
            'attachments.*' => 'attachment',
        ];
    }

    public function messages(): array
    {
        return [
            'services.required' => 'Please select at least one service you would like quoted.',
            'description.min' => 'Please describe the scope in a little more detail so we can quote accurately.',
            'attachments.*.mimes' => 'Attachments must be PDF, Word, Excel, image or ZIP files.',
            'attachments.*.max' => 'Each attachment must be no larger than 10 MB.',
            'website_url.prohibited' => 'Your submission could not be processed.',
        ];
    }

    /** @return array<string, mixed> */
    public function quoteAttributes(): array
    {
        $data = $this->safe()->only([
            'division_id', 'project_title', 'description', 'budget_range', 'currency',
            'timeline', 'preferred_start_date', 'delivery_location', 'contact_name',
            'company_name', 'email', 'phone', 'country', 'city',
        ]);

        foreach (['project_title', 'description', 'company_name', 'contact_name'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = strip_tags($data[$field]);
            }
        }

        $data['currency'] = strtoupper($data['currency'] ?? 'USD');

        return $data;
    }
}
