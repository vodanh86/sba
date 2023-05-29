<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\Customer;
use App\Http\Models\InvitationLetter;
use App\Admin\Controllers\Constant;

class InvitationLetterController extends Controller
{

    public function find(Request $request)
    {
        $id = $request->get('q');
        $invitationLetter = InvitationLetter::find($id);
        $invitationLetter->customer_type = Constant::CUSTOMER_TYPE[$invitationLetter->customer_type];
        $invitationLetter->payment_method = is_null($invitationLetter->payment_method) ? null : Constant::PAYMENT_METHOD[$invitationLetter->payment_method];
        $invitationLetter->vat = is_null($invitationLetter->vat) ? null : Constant::YES_NO[$invitationLetter->vat];
        return $invitationLetter;
    }
    
}
