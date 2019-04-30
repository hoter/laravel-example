<?php

namespace App\Http\Requests\Conversion;

use App\Http\Requests\ApiRequest;

class DocumentUploadRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'id'             => 'required|min:200|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif',
            'selfie'         => 'required|min:200|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif',
            'bank_statement' => 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif',
            'pay_slip'       => 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif',
            'utility_bill'   => 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif',
        ];
    }
}
