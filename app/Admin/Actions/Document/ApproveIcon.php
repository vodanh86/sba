<?php

namespace App\Admin\Actions\Document;

use App\Http\Models\Contract;
use App\Http\Models\StatusTransition;
use Encore\Admin\Actions\RowAction;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;
use Encore\Admin\Facades\Admin;

class ApproveIcon extends RowAction
{
    // After the page clicks on the chart in this column, send the request to the backend handle method to execute
    public function handle(Contract $contract)
    {
        $approveStatus = StatusTransition::where(["table" => Constant::CONTRACT_TABLE, "status_id" => $contract->status, "approve_type" => 2])->where("approvers", 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->first();
        $contract->status = $approveStatus->next_status_id;
        $contract->save();

        return $this->response()->html("&nbsp;");
    }

    public function display($status_id)
    {
        $approveStatus = StatusTransition::where(["table" => Constant::CONTRACT_TABLE, "status_id" => $status_id, "approve_type" => 2])->where("approvers", 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->get();
        return count($approveStatus) == 0 ? "" : "<i class=\"fa fa-check\"></i>";
    }
}
