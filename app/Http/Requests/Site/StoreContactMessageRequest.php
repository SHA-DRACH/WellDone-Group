<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc,dns', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'in:general,sales,careers,support,accounts'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'website_url' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'website_url.prohibited' => 'Your submission could not be processed.',
            'message.min' => 'Please give us a little more detail so we can route your enquiry correctly.',
        ];
    }

    /**
     * Enquiries are stored and displayed as plain text — no markup is
     * accepted from the public form.
     *
     * @return array<string, mixed>
     */
    public function messageAttributes(): array
    {
        $data = $this->safe()->only([
            'name', 'email', 'phone', 'company', 'department', 'subject', 'message',
        ]);

        foreach (['name', 'company', 'subject', 'message'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = strip_tags($data[$field]);
            }
        }

        return $data;
    }
}
