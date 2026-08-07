<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class AssistantAskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:'.config('assistant.max_question_length', 500)],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Type a question first.',
            'message.max' => 'That is a little long — try asking it in a sentence or two.',
        ];
    }

    /**
     * The question, flattened to plain text.
     *
     * It is echoed back into the transcript and sent to a language model, so
     * it never carries markup and never carries the line noise that would let
     * someone dress an instruction up as part of the page.
     */
    public function question(): string
    {
        $question = strip_tags($this->string('message')->toString());
        $question = preg_replace('/\s+/u', ' ', $question) ?? $question;

        return trim($question);
    }
}
