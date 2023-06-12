<?php

Namespace App\Admin\Actions\Document;

use Illuminate\Http\Request;
use App\Http\Models\PreAssessment;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;

class AddPreAssessmentComment extends RowAction
{
     public function handle(PreAssessment $preAssessment, Request $request)
    {
        $preAssessment->comment = Admin::user()->name . " : " . $request->get("note") . "<br/>" . $preAssessment["comment"];
        $preAssessment->save();

        // return a new html to the front end after saving
        $html = $preAssessment->comment ? "<i >$preAssessment->comment</i>" : "<i >$preAssessment->comment</i>" ;

        return $this->response()->html($html);
    }

    public function form()
    {
        $this->text('note', 'Ghi chú phê duyệt');
    }

    public function display($comment)
    {
        $approveStatus = Utils::getAvailbleStatus(Constant::PRE_ASSESS_TABLE, Admin::user()->roles[0]->slug, "approvers");
        if (in_array($this->getRow()->getAttribute("status"), $approveStatus) == 1) {
            return $comment ? "<i >$comment</i>" : "<i >Thêm comment</i>";
        }
        echo($comment);
    }
}