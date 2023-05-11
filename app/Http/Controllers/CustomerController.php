<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\IndividualCustomer;
use App\Http\Models\BusinessCustomer;

class CustomerController extends Controller
{
    //
    public function index(Request $request)
    {
        $type = $request->get('q');
        $branch_id = $request->get('branch_id');

        if ($type == 1){
            return IndividualCustomer::where("branch_id", $branch_id)->get(['id', 'name as text']);
        }
    
        return BusinessCustomer::where("branch_id", $branch_id)->get(['id', 'name as text']);
    }
    
}
