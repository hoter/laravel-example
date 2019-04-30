<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Http\Requests\Account\CreateAccountRequest;
use App\Services\Harlib\HarlibApiException;
use Auth;
use Eloquent;
use Helpers\DataHelper;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    protected $guarded = ['id'];

    protected $hidden = [
        'password', 'api_token',
    ];

    // -----------------------------------------------------------------------------------------------------------------
    // static
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Returns the current user or a guest
     *
     * @return Customer|Guest
     */
    public static function current()
    {
        return Auth::check()
            ? Auth::user()
            : new Guest();
    }

    public static function createFromRequest(CreateAccountRequest $request)
    {
        // data
        $personal         = $request->personal;
        $marketing        = $request->marketing;
        $data             = array_merge($personal, $marketing);
        $data['password'] = bcrypt($data['password']);

        // create user
        $customer = Customer::create($data);

        // addresses
        foreach ($request['addresses'] as $address)
        {
            $address['customer_id'] = $customer->id;
            CustomerAddress::create($address);
        }

        return $customer;
    }

    /**
     * Creates a new, UNSAVED, customer from request data
     *
     * @param CreateAccountRequest $request
     * @return Customer
     */
    public static function newFromRequest(CreateAccountRequest $request)
    {
        // data
        $personal         = $request->personal;
        $marketing        = $request->marketing;
        $data             = array_merge($personal, $marketing);

        // create user
        $customer = new Customer($data);

        // add addresses
        $addresses = collect($request['addresses'])->map(function ($address) {
            return new CustomerAddress($address);
        });
        $customer->setRelation('addresses', $addresses);

        // return
        return $customer;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // methods
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Boot function for using with User Events
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function (Customer $model) {
            //$model->initialize();
        });
    }


    // -----------------------------------------------------------------------------------------------------------------
    // relations
    // -----------------------------------------------------------------------------------------------------------------

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function employment()
    {
        return $this->hasOne(CustomerEmployment::class);
    }

    public function finance()
    {
        return $this->hasOne(CustomerFinance::class);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // accessors
    // -----------------------------------------------------------------------------------------------------------------

    public function getAprAttribute($value)
    {
        return $value ?: 8.9;
    }

    public function index(array $params = [])
    {
        $data = [
            'personal'    => $this->getBasic(),
            'addresses'   => $this->getAddresses(),
            'employment'  => $this->employment,
            'marketing'   => $this->getTerms(),
            'settings'    => [
                'rating' => $this->rating,
            ],
            'finances'  => $this->finance,
        ];

        return array_merge($params, $data);
    }

    // -------------------------------------------------------------------------------------------------------------------
    // getters
    // -------------------------------------------------------------------------------------------------------------------

    public function getBasic()
    {
        return array_only($this->toArray(), [
            'title',
            'first_name',
            'last_name',
            'dob',
            'email',
            'mobile',
            'gender',
            'marital_status',
            'driving_licence',
        ]);
    }

    public function getAddresses()
    {
        return $this->addresses->toArray();
    }

    public function updateAddresses($newData)
    {
        // data
        $oldData  = $this->addresses;
        $buckets  = DataHelper::crudPartition($oldData, $newData);

        // delete missing items
        $buckets['delete']
            ->each(function (CustomerAddress $model) {
                $model->delete();
            });

        // update existing items
        $buckets['update']->each(function ($data) {
            CustomerAddress::find($data['id'])->update((array) $data);
        });

        // create new items
        $addresses = $buckets['create']
            ->map(function ($data) { return new CustomerAddress($data); });
        $this->addresses()->saveMany($addresses);

        // return updated addresses
        $data = CustomerAddress::where(['customer_id' => $this->id])->get();
        return $data;
    }

    public function getTerms()
    {
        return array_only($this->toArray(), [
            'is_email_opt_in',
            'is_sms_opt_in',
        ]);
    }

    public function getLegal()
    {
        return [
            'is_email_opt_in' => 1,
            'is_sms_opt_in'   => 1,
            'is_terms_agreed' => 1,
            'agreed_terms'    => '<h1>Harlib Soft search Terms</h1>',
        ];
    }

    public function getEmployment()
    {
        return $this->employment->getData();
    }


    // -----------------------------------------------------------------------------------------------------------------
    // scopes
    // -----------------------------------------------------------------------------------------------------------------

    public function currentAddress()
    {
        return $this->addresses->where('type', 'current')->first()->toArray();
    }

    public function previousAddress()
    {
        return $this->addresses->where('type', 'previous');
    }

}
