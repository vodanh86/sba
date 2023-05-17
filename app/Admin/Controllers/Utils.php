<?php

namespace App\Admin\Controllers;

use App\Http\Models\StatusTransition;

abstract class Utils
{
    public static function getAvailbleStatus(string $table, string $role, string $permission)
    {
        return StatusTransition::where(["table" => $table])->where($permission, 'LIKE', '%'.$role.'%')->pluck('status_id')->toArray();
    }
}
