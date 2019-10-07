<?php

namespace GP\LocationServiceability\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that GP\LocationServiceability\ly to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page'       => ['sometimes', 'numeric'],
            'lat'        => ['required_with:lng,distance', 'numeric'],
            'lng'        => ['required_with:lat,distance', 'numeric'],
            'distance'   => ['required_with:lat,lng', 'numeric'],
            'state_code' => ['sometimes', 'required', 'string', 'exists:states,code']
        ];
    }
}
