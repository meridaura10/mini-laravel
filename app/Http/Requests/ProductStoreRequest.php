<?php

namespace App\Http\Requests;

use Framework\Kernel\Http\Requests\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['exists:brands,id'],
            'title' => ['string', 'min:4','required'],
            'price' => ['int','min:0'],
        ];
    }
}
