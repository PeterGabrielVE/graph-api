<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o reglas de autorización si aplica
    }

    public function rules(): array
    {
        return [
            'parent' => ['nullable', 'integer', 'exists:nodes,id'],
        ];
    }
}
