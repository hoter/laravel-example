<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ApiRequest extends FormRequest
{
    // override in subclasses
    protected $_rules = [];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $this->_rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response($this->formatErrors($validator)));
    }

    /**
     * Helper function that allows us to pluck array values by path
     *
     * @usage $request->pluck('foo.bar');
     *
     * @param string $path
     * @param bool   $required
     * @return mixed
     * @throws \Exception
     */
    public function pluck($path, $required = false)
    {
        $parameters = $this->all();
        $value = array_get($parameters, $path);
        if (empty($value) && $required) {
            $type = array_has($parameters, $path) ? 'Empty' : 'Missing';
            throw new \Exception("$type parameter `$path`", 422);
        }
        return $value;
    }
}
