<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Admin\Controllers\Constant;
use App\Http\Models\Contract;
use App\Http\Models\StatusTransition;

class ContractController extends Controller
{

    public function find(Request $request)
    {
        $id = $request->get('q');
        $contract = Contract::find($id);
        return $contract;
    }

    public function status(Request $request)
    {
        $type = $request->get('q');
        $status = array();
        $nextStatuses = StatusTransition::where("table", $type == Constant::PRE_CONTRACT_TYPE ? Constant::PRE_CONTRACT_TABLE : Constant::CONTRACT_TABLE )->whereNull("status_id")->get();
        foreach ($nextStatuses as $nextStatus) {
            $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
        }
        return $status;
    }
    
}
