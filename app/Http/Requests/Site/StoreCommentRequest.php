<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:120'],
            'author_email' => ['required', 'email:rfc,dns', 'max:190'],
            'author_website' => ['nullable', 'url', 'max:190'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:blog_comments,id'],
            // Honeypot: a real visitor never fills this in.
            'website_url' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'website_url.prohibited' => 'Your submission could not be processed.',
        ];
    }

    /**
     * Comment attributes are stored as plain text and escaped on output —
     * no HTML is accepted from anonymous visitors.
     */
    public function commentAttributes(): array
    {
        return [
            'author_name' => strip_tags($this->string('author_name')->toString()),
            'author_email' => $this->string('author_email')->toString(),
            'author_website' => $this->input('author_website') ?: null,
            'body' => strip_tags($this->string('body')->toString()),
            'parent_id' => $this->input('parent_id'),
            'is_approved' => false,
            'ip_address' => $this->ip(),
        ];
    }
}
