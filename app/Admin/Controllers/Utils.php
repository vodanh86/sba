<?php

namespace App\Admin\Controllers;

use App\Http\Models\StatusTransition;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Status;
use App\Http\Models\Branch;

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

    public static function getCreateRole($table){
        return StatusTransition::where(["table" => $table])->whereNull('status_id')->pluck('editors')->first()[0];
    }

    public static function generateCode($table, $branchId){
        $code = DB::table($table)
        ->select(DB::raw('code'))
        ->where('code', 'like', '%'.date('ym').'%')
        ->orderByDesc('id')
        ->first();
        $branchCode = Branch::find($branchId)->code;
        if ($code){
            $currentIndex = substr($code->code, 1, 7);
            return "S".($currentIndex + 1).".$branchCode";
        }
        return "S".date('ym')."001.$branchCode";
    }

    public static function generateInvitationCode($table){
        $code = DB::table($table)
        ->select(DB::raw('code'))
        ->where('code', 'like', '%'.date('ym').'%')
        ->orderByDesc('id')
        ->first();
        if ($code){
            $currentIndex = substr($code->code, 1, 7);
            return "A".($currentIndex + 1);
        }
        return "A".date('ym')."001";
    }
}
