<?php

namespace App\Admin\Controllers;

use App\Http\Models\StatusTransition;
use App\Http\Models\Status;

abstract class Utils
{
    public static function getAvailbleStatus(string $table, string $role, string $permission)
    {
        return StatusTransition::where(["table" => $table])->where($permission, 'LIKE', '%'.$role.'%')->pluck('status_id')->toArray();
    }

    public static function getNextStatuses(string $table, string $role)
    {
        $nextStatuses = array();
        $statuses = StatusTransition::where(["table" => $table])->where("approvers", 'LIKE', '%' . $role . '%')->whereIn("approve_type", [1, 2])->get();
        foreach($statuses as $key =>$status){
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }
        return $nextStatuses;
    }

    public static function getEditableStatuses(){

    }
}
