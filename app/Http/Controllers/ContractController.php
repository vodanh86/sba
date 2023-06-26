<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\Contract;

class ContractController extends Controller
{

    public function find(Request $request)
    {
        $id = $request->get('q');
        $contract = Contract::find($id);
        return $contract;
    }
    
}
