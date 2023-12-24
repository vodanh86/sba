<?php

namespace App\Admin\Controllers;

use App\Http\Models\StatusTransition;
use DateTime;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Status;
use App\Http\Models\Branch;
use App\Http\Models\Notification;
use Pusher\Pusher;

abstract class Utils
{
    public static function getAvailbleStatus(string $table, string $role, string $permission)
    {
        return StatusTransition::where(["table" => $table])->where($permission, 'LIKE', '%' . $role . '%')->pluck('status_id')->toArray();
    }

    public static function getAvailbleStatusInTables(array $tables, string $role, string $permission)
    {
        return StatusTransition::whereIn("table", $tables)->where($permission, 'LIKE', '%' . $role . '%')->pluck('status_id')->toArray();
    }

    public static function getNextStatuses(string $table, string $role)
    {
        $nextStatuses = array();
        $statuses = StatusTransition::where(["table" => $table])->where("approvers", 'LIKE', '%' . $role . '%')->whereIn("approve_type", [1, 2])->get();
        foreach ($statuses as $key => $status) {
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }
        return $nextStatuses;
    }

    public static function getCreateRole($table)
    {
        return StatusTransition::where(["table" => $table])->whereNull('status_id')->pluck('editors')->first()[0];
    }

    public static function sendNotification($userId, $table)
    {

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

    public static function generateCode($table, $branchId, $type)
    {
        $query = DB::table($table)
            ->select(DB::raw('code'))
            ->where('branch_id', $branchId)
            ->where('code', 'like', '%' . date('ym') . '%');
        if ($type == "pre_contracts") {
            $query->where("contract_type", 0);
        } else {
            $query->where("contract_type", 1);
        }
        $code = $query->orderByDesc('id')->first();
        $branchCode = Branch::find($branchId)->code;
        if ($code) {
            $currentIndex = substr($code->code, 1, 7);
            if ($type == "pre_contracts") {
                return "KS" . ($currentIndex + 1) . ".$branchCode";
            } else {
                return "S" . ($currentIndex + 1) . ".$branchCode";
            }
        }
        return "S" . date('ym') . "001.$branchCode";
    }

    public static function generateInvitationCode($table)
    {
        $code = DB::table($table)
            ->select(DB::raw('code'))
            ->where('code', 'like', '%' . date('ym') . '%')
            ->orderByDesc('id')
            ->first();
        if ($code) {
            $currentIndex = substr($code->code, 1, 7);
            return "A" . ($currentIndex + 1);
        }
        return "A" . date('ym') . "001";
    }

    public static function checkContractStatus($contract)
    {
        if ($contract->contract_type == Constant::PRE_CONTRACT_TYPE) {
            foreach ($contract->preAssessments as $i => $preAssessment) {
                if (Status::find($preAssessment->status)->done == 1) {
                    return 1;
                }
            }
        } else {
            foreach ($contract->contractAcceptances as $i => $contractAcceptance) {
                if (Status::find($contractAcceptance->status)->done == 1) {
                    return 1;
                }
            }
        }
        return 0;
    }

    public static function checkContractEndDate($contract)
    {
        if ($contract->contract_type == Constant::PRE_CONTRACT_TYPE) {
            foreach ($contract->preAssessments as $i => $preAssessment) {
                if (Status::find($preAssessment->status)->done == 1) {
                    return Status::find($preAssessment->status)->finished_date;
                }
            }
        } else {
            foreach ($contract->contractAcceptances as $i => $contractAcceptance) {
                if (Status::find($contractAcceptance->status)->done == 1) {
                    return $contract->to_date;
                }
            }
        }
        return "";
    }

    public static function isSuperManager($id)
    {
        if ($id == 17 || $id == 18) {
            return true;
        }
        return false;
    }
    public static function convertHundred($number, $units, $teens, $tens, $hundreds)
    {
        $result = '';

        if ($number > 99) {
            $result .= $hundreds[floor($number / 100)] . ' ';
            $number %= 100;
        }

        if ($number > 9 && $number < 20) {
            $result .= $teens[$number - 10];
        } else {
            $result .= $tens[floor($number / 10)] . ' ';
            $result .= $units[$number % 10];
        }

        return $result;
    }
    public static function numberToWords($number)
    {
        $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $teens = ['', 'mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
        $tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        $hundreds = ['', 'một trăm', 'hai trăm', 'ba trăm', 'bốn trăm', 'năm trăm', 'sáu trăm', 'bảy trăm', 'tám trăm', 'chín trăm'];
        if ($number == 0) {
            return 'Không';
        }
        $result = '';
        $billions = floor($number / 1000000000);
        $millions = floor(($number - $billions * 1000000000) / 1000000);
        $thousands = floor(($number - $billions * 1000000000 - $millions * 1000000) / 1000);
        $remainder = $number - $billions * 1000000000 - $millions * 1000000 - $thousands * 1000;

        if ($billions > 0) {
            $result .= self::convertHundred($billions, $units, $teens, $tens, $hundreds) . ' tỷ ';
        }

        if ($millions > 0) {
            $result .= self::convertHundred($millions, $units, $teens, $tens, $hundreds) . ' triệu ';
        }

        if ($thousands > 0) {
            $result .= self::convertHundred($thousands, $units, $teens, $tens, $hundreds) . 'nghìn ';
        }

        if ($remainder > 0) {
            $result .= self::convertHundred($remainder, $units, $teens, $tens, $hundreds);
        }
        $resultFix = ucfirst($result) . 'đồng';
        return $resultFix;
    }
    public static function generateDate()
    {
        $today = new DateTime();
        $day = $today->format('d');
        $month = $today->format('m');
        $year = $today->format('Y');
        return 'ngày ' . $day . ' tháng ' . $month . ' năm ' . $year;
    }
}
