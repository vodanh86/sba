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
            return IndividualCustomer::where("branch_id", $branch_id)->get(['id', 'id_number as text']);
        }
    
        return BusinessCustomer::where("branch_id", $branch_id)->get(['id', 'tax_number as text']);
    }

    public function find(Request $request)
    {
        $id = $request->get('q');
        $type = $request->get('type');

        if ($type == 1){
            return IndividualCustomer::find($id);
        }
    
        return BusinessCustomer::find($id);
    }
    
}
