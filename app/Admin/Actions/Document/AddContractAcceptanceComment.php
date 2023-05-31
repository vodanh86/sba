<?php

Namespace App\Admin\Actions\Document;

use Illuminate\Http\Request;
use App\Http\Models\ContractAcceptance;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;

class AddContractAcceptanceComment extends RowAction
{
     public function handle(ContractAcceptance $contractAcceptance, Request $request)
    {
        $contractAcceptance->comment = Admin::user()->name . " : " . $request->get("note") . "<br/>" . $contractAcceptance["comment"];
        $contractAcceptance->save();

        // return a new html to the front end after saving
        $html = $contractAcceptance->comment ? "<i >$contractAcceptance->comment</i>" : "<i >$contractAcceptance->comment</i>" ;

        return $this->response()->html($html);
    }

    public function form()
    {
        $this->text('note', 'Ghi chú phê duyệt');
    }

    public function display($comment)
    {
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "approvers");
        if (in_array($this->getRow()->getAttribute("status"), $approveStatus) == 1) {
            return $comment ? "<i >$comment</i>" : "<i >Thêm comment</i>";
        }
        echo($comment);
    }
}