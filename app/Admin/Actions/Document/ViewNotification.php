<?php

Namespace App\Admin\Actions\Document;

use App\Http\Models\Notification;
use Encore\Admin\Actions\RowAction;
use App\Admin\Controllers\Utils;

class ViewNotification extends RowAction
{
    // After the page clicks on the chart in this column, send the request to the backend handle method to execute
    public function handle(Notification $notification)
    {
        if ($notification->check == 0){
            $notification->check = 1;
        }
        $notification->save();
        Utils::sendNotification($notification->user_id, $notification->table);
        return $this->response()->redirect(env('APP_URL')."/admin/".$notification->table."/".$notification->table_id);
      }

     public function display($check)
    {
        return $check == 0 ? "<i class=\"fa fa-envelope-o\"></i></a>" : "<i class=\"fa fa-envelope-open-o\"></i>";
    }
}