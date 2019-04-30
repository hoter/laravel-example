<?php namespace App\Services\Api;

use App\Http\Requests\Application\CreateApplicationRequest;
use App\Models\Customer;
use App\Models\Stock;
use App\Services\Harlib\HarlibApi;
use App\Services\Harlib\HarlibApiException;
use Exception;
use Illuminate\Http\Request;

/**
 * Service to manage collation of data to and from Harlib
 */
class HarlibService
{
    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @var HarlibApi
     */
    protected $api;

    /**
     * @var Customer
     */
    protected $user;


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation
    // -----------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        $this->api  = app(HarlibApi::class);
        $this->user = Customer::current();
    }


    // -----------------------------------------------------------------------------------------------------------------
    // accessors
    // -----------------------------------------------------------------------------------------------------------------

    public function getApplicationData(CreateApplicationRequest $request)
    {
        // data
        $customer = $this->user;
        $data     = $this->getOffersData($request);
        $extra    = [
            'employments'     => $this->getEmployment($customer),
            'income_expenses' => $this->getFinances($request),
            'bank'            => $this->getBank($request),
            'security'        => $this->getSecurity($request),
        ];

        // return
        return array_merge($data, $extra);
    }

    /**
     * Gets the core data required before:
     *
     * - creating an application
     * - getting offers
     *
     * @param Request $request
     *
     * @return array
     * @throws Exception
     */
    public function getOffersData(Request $request)
    {
        // objects
        $customer = $this->user;

        // variables
        $loan = (object) $request->loan;
        $car  = $this->getCar($request->car_id, $loan);

        // update loan
        $loan->amount = $car->customer_price;
        $loan->gfv    = $car->gfv;

        // return
        return [
            'customer'  => $customer->getBasic(),
            'addresses' => $customer->getAddresses(),
            'legal'     => $customer->getLegal(),
            'loan'      => $loan,
            'car'       => $car,
        ];
    }


    /**
     * Gets the core data required before:
     *
     * - creating an application
     * - getting Finance Only offers
     *
     * @param Request $request
     *
     * @return array
     * @throws Exception
     */
    public function getFinanceOnlyData(Request $request)
    {
        // objects
        $customer = $this->user;

        // return
        return [
            'customer'  => $customer->getBasic(),
            'addresses' => $customer->getAddresses(),
            'legal'     => $customer->getLegal(),
            'loan'      => $request->loan,
        ];
    }

    /**
     * Gets the car and car price
     *
     * @param   int    $car_id
     * @param   object $loan
     * @return  object
     * @throws  Exception
     */
    public function getCar($car_id, $loan)
    {
        $car = Stock::find($car_id)->getHarlibData($loan->term, $loan->mileage);

        /**
         * For PCP and Balloon product, we must have GFV in database.
         * Hot fix to get the HP working for investor demo
         */
        if ($loan->product == 'new_car_pcp' || (isset($loan->type) && $loan->type == 'balloon'))
        {
            if (empty($car->gfv))
            {
                throw new Exception("Could not find matching gfv for selected product");
            }
        }

        return $car;
    }

    public function getEmployment(Customer $customer)
    {
        // fetch employment
        $employment = $customer->employment;
        if (!$employment)
        {
            throw new ApiException('Customer is missing employment data');
        }

        // variables
        $basic      = ['job_title', 'employer', 'address', 'phone', 'time_at_employment', 'basis', 'type', 'status'];
        $address    = ['street_no', 'street', 'town', 'district', 'county', 'postcode'];
        $employment = $employment->toArray();

        if($employment['status'] == 'working_student')
        {
            $employment['status'] = 'student';
        }

        // build data
        $data            = array_flip($basic);
        $data            = array_merge($data, array_only($employment, $basic));
        $data['address'] = array_only($employment, $address);


        // hacks
        $data['length'] = $data['time_at_employment'];
        // throw new HarlibApiException($data['phone']);

        return [
            $data,
        ];
    }

    public function getFinances(Request $request)
    {
        $data = (object) $request->finances;
        return [
            'gross_monthly_income'  => $data->gross_annual_income / 12,
            'net_monthly_income'    => $data->net_monthly_income,
            'total_monthly_expense' => $data->total_monthly_expenses, // FIXME Harlib should have "expenses" not "expense"
            'has_mortgage'          => $data->has_mortgage,
            'month_at_mortgage'     => $data->time_with_mortgage,
        ];
    }

    public function getBank(Request $request)
    {
        $data = (object) $request->finances;
        return [
            'sort_code'     => $data->sort_code,
            'account_no'    => $data->account_no,
            'month_at_bank' => $data->time_with_bank,
        ];
    }

    public function getSecurity(Request $request)
    {
        return $request->security;
    }


// -----------------------------------------------------------------------------------------------------------------
// user methods
// -----------------------------------------------------------------------------------------------------------------

    /**
     * Creates a new Back Office user from a new UNSAVED Front Office user
     *
     * @param Customer $user
     * @return Customer
     */
    public function createUser(Customer $user)
    {
        // convert the customer to data suitable for Harlib
        $params = [
            'customer'  => $user->getBasic(),
            'addresses' => $user->getAddresses(),
            'legal'     => $user->getLegal(),
        ];

        // add password to data
        // at this point the password should NOT be encypted!
        $params['customer']['password'] = $user->password;
        $params['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
        // attempt to create user on Harlib
        $response = $this->api->post('customer/create', $params); // not customer/create !
        \Log::info(print_r(compact('params', 'response'), 1));

        // update and return
        $user->api_token = $response->api_token;
        return $user;
    }

    public function loginUser($credentials)
    {
        $response = $this->api->post('customer/login', $credentials);
        $this->updateToken($response->api_token);
    }

    public function updateToken($token)
    {
        $this->user->update(['api_token' => $token]);
    }

    public function resetPassword($email, $password)
    {
        return $this->api->post('/customer/reset-password', [
            'email'     => $email,
            'new_password'  => $password
        ]);
    }
}