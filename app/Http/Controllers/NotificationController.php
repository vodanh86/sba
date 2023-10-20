<?php

namespace App\Http\Controllers;

use App\Http\Models\AdminUser;
use App\Http\Models\Notification;
use Pusher\Pusher;
use App\Admin\Controllers\Constant;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('status', 0)->get();
        foreach ($notifications as $notification) {
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
            $pusher->trigger(Constant::PUSHER_CHANNEL, Constant::PUSHER_EVENT, $notification);
            $notification['status'] = 1;
            $notification->save();
        }
        return response()->json(200);
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
}
