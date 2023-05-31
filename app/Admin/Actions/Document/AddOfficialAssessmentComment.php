<?php

Namespace App\Admin\Actions\Document;

use Illuminate\Http\Request;
use App\Http\Models\OfficialAssessment;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;

class AddOfficialAssessmentComment extends RowAction
{
     public function handle(OfficialAssessment $officialAssessment, Request $request)
    {
        $officialAssessment->comment = Admin::user()->name . " : " . $request->get("note") . "<br/>" . $officialAssessment["comment"];
        $officialAssessment->save();

        // return a new html to the front end after saving
        $html = $officialAssessment->comment ? "<i >$officialAssessment->comment</i>" : "<i >$officialAssessment->comment</i>" ;

        return $this->response()->html($html);
    }

    public function form()
    {
        $this->text('note', 'Ghi chú phê duyệt');
    }

    public function display($comment)
    {
        $approveStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "approvers");
        if (in_array($this->getRow()->getAttribute("status"), $approveStatus) == 1) {
            return $comment ? "<i >$comment</i>" : "<i >Thêm comment</i>";
        }
        echo($comment);
    }
}