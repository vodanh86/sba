<?php

namespace App\Admin\Controllers;

use App\Http\Models\StatusTransition;

abstract class Utils
{
    public static function getAvailbleStatus(string $table, string $role, string $permission)
    {
        if ($permission == "view") {
            return StatusTransition::where(["table" => $table])->where('viewers', 'LIKE', '%'.$role.'%')->pluck('status_id')->toArray();
        } else {
            return StatusTransition::where(["table" => $table])->where('editors', 'LIKE', '%'.$role.'%')->pluck('status_id')->toArray();
        }
    }
}
