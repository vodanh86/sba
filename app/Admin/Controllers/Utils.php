<?php

namespace App\Admin\Controllers;

use App\Http\Models\StatusTransition;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Status;
use App\Http\Models\Branch;
use App\Http\Models\Notification;
use Pusher\Pusher;

abstract class Utils
{
    public static function getAvailbleStatus(string $table, string $role, string $permission)
    {
        return StatusTransition::where(["table" => $table])->where($permission, 'LIKE', '%'.$role.'%')->pluck('status_id')->toArray();
    }

    public static function getAvailbleStatusInTables(array $tables, string $role, string $permission)
    {
        return StatusTransition::whereIn("table", $tables)->where($permission, 'LIKE', '%'.$role.'%')->pluck('status_id')->toArray();
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

    public static function sendNotification($userId, $table){

        $data = array();
        $data["userId"] = $userId;
        $data["table"] = $table;
        $data["count"] = Notification::where("user_id", $userId)->where("check", 0)->count();
        $options = array(
            'cluster' => 'ap1',
            'encrypted' => true
        );

        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );

        $pusher->trigger(Constant::PUSHER_CHANNEL, Constant::PUSHER_EVENT, $data);
    }

    public static function generateCode($table, $branchId){
        $code = DB::table($table)
        ->select(DB::raw('code'))
        ->where('branch_id', $branchId)
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

    public static function checkContractStatus($contract) {
        if ($contract->contract_type == Constant::PRE_CONTRACT_TYPE) {
            foreach ($contract->preAssessments as $i=>$preAssessment) {
                if (Status::find($preAssessment->status)->done == 1) {
                    return 1;
                }
            }   
        } else {
            foreach ($contract->contractAcceptances as $i=>$contractAcceptance) {
                if (Status::find($contractAcceptance->status)->done == 1) {
                    return 1;
                }
            }
        }
        return 0;
    }

    public static function checkContractEndDate($contract) {
        if ($contract->contract_type == Constant::PRE_CONTRACT_TYPE) {
            foreach ($contract->preAssessments as $i=>$preAssessment) {
                if (Status::find($preAssessment->status)->done == 1) {
                    return Status::find($preAssessment->status)->finished_date;
                }
            }   
        } else {
            foreach ($contract->contractAcceptances as $i=>$contractAcceptance) {
                if (Status::find($contractAcceptance->status)->done == 1) {
                    return $contract->to_date;
                }
            }
        }
        return "";
    }

    public static function isSuperManager($id){
        if ($id == 17 || $id == 18){
            return true;
        } 
        return false;
    }
}
