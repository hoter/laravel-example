<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;

class PasswordResetLinkEmail extends Mailable
{

    public  $customer  = null;
    /**
     * Create a new message instance.
     *
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.password-reset-email');
    }
}
