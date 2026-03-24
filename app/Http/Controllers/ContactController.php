<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function email(): RedirectResponse
    {
        $email = config('legal.contact_email', config('mail.from.address'));

        return redirect()->away("mailto:{$email}");
    }
}
