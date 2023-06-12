<?php

Namespace App\Admin\Actions\Document;

use Illuminate\Http\Request;
use App\Http\Models\TaskNote;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Utils;
use App\Admin\Controllers\Constant;

class AddTaskNoteComment extends RowAction
{
     public function handle(TaskNote $taskNote, Request $request)
    {
        $taskNote->comment = Admin::user()->name . " : " . $request->get("note") . "<br/>" . $taskNote["comment"];
        $taskNote->save();

        // return a new html to the front end after saving
        $html = $taskNote->comment ? "<i >$taskNote->comment</i>" : "<i >$taskNote->comment</i>" ;

        return $this->response()->html($html);
    }

    public function form()
    {
        $this->text('note', 'Ghi chú phê duyệt');
    }

    public function display($comment)
    {
        $approveStatus = Utils::getAvailbleStatus(Constant::TASK_NOTE_TABLE, Admin::user()->roles[0]->slug, "approvers");
        if (in_array($this->getRow()->getAttribute("status"), $approveStatus) == 1) {
            return $comment ? "<i >$comment</i>" : "<i >Thêm comment</i>";
        }
        echo($comment);
    }
}