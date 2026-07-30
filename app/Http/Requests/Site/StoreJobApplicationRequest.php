<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc,dns', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'portfolio_url' => ['nullable', 'url', 'max:190'],
            'linkedin_url' => ['nullable', 'url', 'max:190'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'highest_qualification' => ['nullable', 'string', 'max:150'],
            'expected_salary' => ['nullable', 'string', 'max:80'],
            'available_from' => ['nullable', 'date', 'after_or_equal:today'],

            /*
             * `mimes` checks the detected MIME type rather than the filename,
             * so a renamed executable is rejected. 5 MB is enough for a CV
             * and keeps the private disk manageable.
             */
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],

            'consent' => ['accepted'],
            'website_url' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'resume' => 'résumé',
            'consent' => 'consent to process your data',
        ];
    }

    public function messages(): array
    {
        return [
            'resume.mimes' => 'Please upload your résumé as a PDF or Word document.',
            'resume.max' => 'Your résumé must be no larger than 5 MB.',
            'website_url.prohibited' => 'Your submission could not be processed.',
        ];
    }

    /** @return array<string, mixed> */
    public function applicationAttributes(): array
    {
        return $this->safe()->only([
            'full_name', 'email', 'phone', 'country', 'city', 'cover_letter',
            'portfolio_url', 'linkedin_url', 'years_experience',
            'highest_qualification', 'expected_salary', 'available_from',
        ]);
    }
}
