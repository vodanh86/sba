<?php

namespace App\Http\Controllers;

use App\Http\Models\AdminUser;
use App\Http\Models\Notification;
use App\Http\Models\NotifyLog;
use App\Http\Models\NotifyStatus;
use Carbon\Carbon;
use Pusher\Pusher;
use App\Admin\Controllers\Constant;

class NotificationController extends Controller
{
    public function index()
    {
        $convertIdToNameUser = function ($userId) {
            $adminUser = AdminUser::find($userId);
            return $adminUser ? $adminUser->name : '';
        };
        $notifyLog = new NotifyLog();
        $notifyStatus = NotifyStatus::where('id', 1)->first();
        if ($notifyStatus->status == 1) {
            return response()->json(200);
        } else {
            $notifyStatus->status = 1;
            $notifyStatus->save();

            $processRecord = 0;
            $notifyLog->time_start = Carbon::now();

            $userIds = Notification::where('status', 0)->groupBy('user_id')->get('user_id')->toArray();
            $batch = [];
            foreach ($userIds as $userId) {
                if ($userId && $userId["user_id"]){
                    $data = array();
                    $data["user_id"] = $userId["user_id"];
                    $data["count"] = Notification::where('check', 0)->where('user_id', $data["user_id"])->get()->count();
                    $batch[] = ['channel' => Constant::PUSHER_CHANNEL, 'name' => Constant::PUSHER_EVENT, 'data' => $data];
    
                    $processRecord++;
                    $notifyLog->time_end = Carbon::now();
                }
            }
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
            $pusher->trigger(Constant::PUSHER_CHANNEL, Constant::PUSHER_EVENT, $batch);
            if ($processRecord > 0) {
                $notifyLog->process_record = $processRecord;
                $notifyLog->save();
            }

            $notifyStatus->status = 0;
            $notifyStatus->save();

            return response()->json(200);
        }
    }

    public function check($id)
    {
        $notification = Notification::find($id);

        if ($notification && $notification->check == 0) {
            $notification->check = 1;
            $notification->save();
            $options = [
                'cluster' => 'ap1',
                'encrypted' => true,
            ];
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            if ($notification->wasChanged('check')) {
                $pusher->trigger(Constant::PUSHER_CHANNEL, Constant::PUSHER_EVENT, $notification);
                return response()->json(['message' => 'Check updated successfully']);
            } else {
                return response()->json(['message' => 'Notification already checked or not found to update'], 400);
            }
        }
        return response()->json(['message' => 'No notification found to update'], 404);
    }

    public function get($userId)
    {
        $notifications = Notification::where('user_id', $userId)->orderBy('id', 'DESC')->take(15)->get();
        return view('notifications', compact('notifications'))->render();
        return response()->json(['notifications' => $notifications], 200);
    }
}
