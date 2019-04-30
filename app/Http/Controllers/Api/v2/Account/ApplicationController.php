<?php

namespace App\Http\Controllers\Api\v2\Account;

use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Conversion\CallValidateAnswersRequest;
use App\Http\Requests\Conversion\CallValidateQuestionsRequest;
use App\Http\Requests\Conversion\DocumentUploadRequest;
use App\Http\Requests\Conversion\IdCheckRequest;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Services\Harlib\HarlibApi;
use App\Services\Harlib\HarlibApiException;
use Illuminate\Http\JsonResponse;

/**
 * Resource controller for applications
 */
class ApplicationController extends ApiController
{
    /**
     * Show all applications made by the current user
     *
     * @param HarlibApi $api
     *
     * @return JsonResponse
     * @throws HarlibApiException
     */
    public function index(HarlibApi $api)
    {
        $data = $api->get('applications')->all();
        foreach ($data as &$application)
        {
            $application = $this->makeApplication($application);
        }
        return $data;
    }

    /**
     * Show single application
     *
     * @param HarlibApi $api
     * @param int       $id
     *
     * @return JsonResponse
     * @throws HarlibApiException
     */
    public function show(HarlibApi $api, $id)
    {
        $data = $api->get("applications/$id")->all();
        return $this->makeApplication($data);
    }

    /**
     * Show single application
     *
     * @param HarlibApi $api
     * @param int       $id
     *
     * @return JsonResponse
     */
    public function status(HarlibApi $api, $id)
    {
        return $api->get("applications/$id")->all();
    }

    public function submitDocuments(HarlibApi $api, DocumentUploadRequest $request, $applicationId)
    {
        $inputs['application_id'] = $applicationId;
        return $api->post("customer/documents-upload", $inputs, $request->allFiles());
    }

    public function checkId(IdCheckRequest $request, HarlibApi $api, $applicationId)
    {
        return $api->post("applications/$applicationId/verify-id", null, $request->allFiles());
    }

    public function getQuestions(CallValidateQuestionsRequest $request, HarlibApi $api, $applicationId)
    {
        return $api->get("applications/$applicationId/credit-questions", $request->all());
    }

    public function postAnswers(HarlibApi $api, CallValidateAnswersRequest $request, $applicationId)
    {
        return $api->post("applications/$applicationId/credit-questions", $request->all());
    }

    protected function makeApplication($data)
    {
        // FIXME need a better way than parsing a "message" fail
        if (array_get($data, 'message') === 'Application not found')
        {
            throw new HarlibApiException($data['message']);
        }
        $stockId     = $data['stock_id'];
        $stock       = Stock::find($stockId);
        if(is_null($stock)) {
            $stock = StockHistory::find($stockId);
        }
        $data['car'] = $stock
            ? $stock->getData()
            : null;
        return $data;
    }

}

