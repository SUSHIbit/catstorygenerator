<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentFileType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !$value->isValid()) {
            $fail('The file is not valid.');
            return;
        }

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];

        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

        $extension = strtolower($value->getClientOriginalExtension());
        $mimeType = $value->getMimeType();

        if (!in_array($extension, $allowedExtensions) || !in_array($mimeType, $allowedMimes)) {
            $fail('Only PDF, DOC, DOCX, PPT, and PPTX files are allowed.');
        }
    }
}