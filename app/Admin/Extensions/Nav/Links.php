<?php

namespace App\Admin\Extensions\Nav;

use Encore\Admin\Facades\Admin;
use App\Http\Models\Notification;

class Links
{
    public function __toString()
    {
    $name = Admin::user()->name;
    $appKey = env('PUSHER_APP_KEY');
    $userId = Admin::user()->id;
    $count = Notification::where("user_id", $userId)->where("check", 0)->count();
    $url = env('APP_URL')."/admin/notifications";
        return 
            <<<HTML
                <li class="dropdown user user-menu dropdown-notifications">
                    <!-- Menu Toggle Button -->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i data-count="$count" class="glyphicon glyphicon-bell notification-icon"></i>
                    </a>
                    <div class="dropdown-container" style="width:40px">
                        <div class="dropdown-toolbar">
                            <div class="dropdown-toolbar-actions">
                                <a href="$url" target="_blank">Xem</a>
                            </div>
                            <h3 class="dropdown-toolbar-title">Notifications (<span class="notif-count">$count</span>)</h3>
                        </div>
                    </div>
                </li>
                <li>
                    <p style="font-weight: bold; margin-top: 15px; color: #fff;">Xin chào: $name</p>
                </li>
                <script type="text/javascript">
                    var notificationsWrapper   = $('.dropdown-notifications');
                    var notificationsCountElem = notificationsWrapper.find('i[data-count]');
                    var notifications          = notificationsWrapper.find('.notif-count');

                    $( document ).ready(function() {

                        var pusher = new Pusher("$appKey", {
                            cluster: 'ap1',
                            encrypted: true
                        });

                        // Subscribe to the channel we specified in our Laravel Event
                        var channel = pusher.subscribe('Notify');
                        // Bind a function to a Event (the full Laravel class)
                        channel.bind('send-message', function(data) {
                            if (data.userId == $userId){
                                notificationsCountElem.attr('data-count', data.count);
                                notificationsWrapper.find('.notif-count').text(data.count);
                            }
                        });
                    });
                </script>
            HTML;
    }
}