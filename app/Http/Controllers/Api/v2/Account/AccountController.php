<?php

namespace App\Http\Controllers\Api\v2\Account;

use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Account\AddressDetailsRequest;
use App\Http\Requests\Account\EmploymentDetailsRequest;
use App\Http\Requests\Account\FinancesRequest;
use App\Http\Requests\Account\PersonalDetailsRequest;
use App\Http\Requests\Account\CreateAccountRequest;
use App\Models\Customer;
use App\Services\Api\HarlibService;
use Illuminate\Http\JsonResponse;
use JWTAuth;

/**
 * Resource controller for account
 */
class AccountController extends ApiController
{
    /**
     * Get a user's complete data
     *
     * @return JsonResponse
     */
    public function index()
    {
        return $this->send(Customer::current()->index());
    }

    /**
     * Create a new user
     *
     * The process is a little complex as we need to create a user on Harlib first:
     *
     *  - create a new Customer, but DON'T save
     *  - pass the customer data to the Harlib service, and request to save
     *  - if there's an error, it will be caught and re-thrown
     *  - if all ok, update the token
     *  - finally, encrypt the password, and save the customer
     *  - then, save all the related addresses
     *
     * Regarding the "new User" there might be a more Laravel way to do this, including the relations
     *
     * @param CreateAccountRequest $request
     *
     * @return JsonResponse
     */
    public function create(CreateAccountRequest $request)
    {
        // create new user
        $customer = Customer::newFromRequest($request);

        // throw new \Exception($request->personal['email']);

        // create user on remote system
        $customer = app(HarlibService::class)->createUser($customer);

        // if the user was created OK on harlib, this next bit of code will run...
        $customer->password = bcrypt($customer->password);

        // save customer
        $customer->save();
        foreach ($customer->addresses as $address)
        {
            $address->customer_id = $customer->id;
            $address->save();
        }

        // login customer
        // Auth::login($customer);
        // TODO centralise this code / find a better way to add auth headers
        $token   = JWTAuth::fromUser($customer);
        $headers = [
            'Authorization' => "Bearer $token",
        ];
        return $this->send([
            'id'         => $customer->id,
            'auth_token' => $token,
        ], $headers);
    }

    public function personal(PersonalDetailsRequest $request)
    {
        $data = $request->except('password');
        Customer::current()->update($data);
        return $data;
    }

    public function addresses(AddressDetailsRequest $request)
    {
        return Customer::current()->updateAddresses($request->addresses);
    }

    public function employment(EmploymentDetailsRequest $request)
    {
        $data = $request->all();
        Customer::current()->employment()->updateOrCreate($data);
        return $data;
    }

    public function finances(FinancesRequest $request)
    {
        $data = $request->only(['gross_annual_income', 'net_monthly_income', 'total_monthly_expenses', 'has_mortgage', 'time_with_mortgage']);
        Customer::current()->finance()->updateOrCreate($data);
        return $data;
    }
}
