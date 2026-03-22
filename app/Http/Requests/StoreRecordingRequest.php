<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:mp4,webm,mov,avi', 'max:512000'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
