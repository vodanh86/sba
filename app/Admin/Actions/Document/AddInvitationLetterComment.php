<?php

Namespace App\Admin\Actions\Document;

use Illuminate\Http\Request;
use App\Http\Models\InvitationLetter;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;

class AddInvitationLetterComment extends RowAction
{
     public function handle(InvitationLetter $invitationLetter, Request $request)
    {
        $invitationLetter->comment = Admin::user()->name . " : " . $request->get("note") . "<br/>" . $invitationLetter["comment"];
        $invitationLetter->save();

        // return a new html to the front end after saving
        $html = $invitationLetter->comment ? "<i >$invitationLetter->comment</i>" : "<i >$invitationLetter->comment</i>" ;

        return $this->response()->html($html);
    }

    public function form()
    {
        $this->text('note', 'Ghi chú phê duyệt');
    }

    public function display($comment)
    {
        $approveStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "approvers");
        if (in_array($this->getRow()->getAttribute("status"), $approveStatus) == 1) {
            return $comment ? "<i >$comment</i>" : "<i >Thêm comment</i>";
        }
        echo($comment);
    }
}