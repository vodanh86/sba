<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\IndividualCustomer;
use App\Http\Models\InvitationLetter;

class InvitationLetterController extends Controller
{

    public function find(Request $request)
    {
        $id = $request->get('q');
        return InvitationLetter::find($id);
    }
    
}
