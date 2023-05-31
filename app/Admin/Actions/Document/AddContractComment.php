<?php

Namespace App\Admin\Actions\Document;

use Illuminate\Http\Request;
use App\Http\Models\Contract;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;

class AddContractComment extends RowAction
{
     public function handle(Contract $contract, Request $request)
    {
        $contract->comment = Admin::user()->name . " : " . $request->get("note") . "<br/>" . $contract["comment"];
        $contract->save();

        // return a new html to the front end after saving
        $html = $contract->comment ? "<i >$contract->comment</i>" : "<i >$contract->comment</i>" ;

        return $this->response()->html($html);
    }

    public function form()
    {
        $this->text('note', 'Ghi chú phê duyệt');
    }

    public function display($comment)
    {
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "approvers");
        if (in_array($this->getRow()->getAttribute("status"), $approveStatus) == 1) {
            return $comment ? "<i >$comment</i>" : "<i >Thêm comment</i>";
        }
        echo($comment);
    }
}