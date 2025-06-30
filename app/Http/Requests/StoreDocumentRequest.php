<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:2097152', // 2GB (2 * 1024 * 1024 KB) - UNLIMITED
                'mimes:pdf,doc,docx,ppt,pptx'
            ],
            'title' => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed 2GB.',
            'file.mimes' => 'Only PDF, DOC, DOCX, PPT, and PPTX files are allowed.',
            'title.max' => 'The title must not exceed 255 characters.',
        ];
    }
}