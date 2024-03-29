<?php

namespace App\Admin\Extensions\Nav;

use App\Http\Models\AdminUser;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use App\Http\Models\Notification;

class Links
{
    public function __toString()
    {
        $name = Admin::user()->name;
        $appKey = env('PUSHER_APP_KEY');
        $userId = Admin::user()->id;
        $userAvartar = Admin::user()->avatar;
        $count = Notification::where("user_id", $userId)->where("check", 0)->count();
        $notifications = Notification::where("user_id", $userId)->where("check", 0)->orderBy("id", "desc")->get();
        $urlNotifications = env('APP_URL') . "/admin/notifications";
        $urlBase = env('APP_URL') . "/admin";
        $urlApiBase = env('APP_URL') . "/api";
        $convertIdToNameUser = function ($userId) {
            $adminUser = AdminUser::find($userId);
            return $adminUser ? $adminUser->name : '';
        };
        $formattedCreatedAt = function ($createdAt) {
            return Carbon::parse($createdAt)->timezone(config('app.timezone'))->format('d/m/Y - H:i:s');
        };
        $listContent = '';
        foreach ($notifications as $notify) {
            $listContent .= <<<HTML
                <div class="card" style="cursor: pointer;" data-notification-id="{{ $notify->id }}">
                    <a class="card-body" href="$urlBase/$notify->table/$notify->table_id" onclick="updateCheckNotification('$notify->id')">
                        <div class="media-left">
                            <div class="media-object">
                                <img src="$userAvartar" class="img-circle" alt="50x50" style="width: 50px; height: 50px;">
                            </div>
                        </div>
                        <div class="media-body">
                            <strong class="notification-title">Gửi từ: {$convertIdToNameUser($notify->user_send)}</strong>
                            <p class="notification-desc">$notify->content</p>
                            <div class="notification-meta">
                                <small class="timestamp">$notify->created_at</small>
                            </div>
                        </div>
                    </a>
                </div>
            HTML;
        }
        return <<<HTML
            <style>
                .card{
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    margin: 20px 10px 20px 10px;
                }
                .card-body {
                    display: flex;
                    justify-content: space-between;
                    align-items: self-start;
                    padding: 20px;
                }
                .card-title {
                    font-size: 16px;
                }
                .card-text {
                    font-size: 14px;
                }
                .dropdown-container{
                    max-width: 250px !important;
                    max-height: 450px !important;
                    overflow: hidden;
                    overflow-y: scroll;
                }
                .dropdown-footer{
                    position: sticky;
                    bottom: 0;
                }
            </style>
            <li class="dropdown user user-menu dropdown-notifications">
                <!-- Menu Toggle Button -->
                <a href="#" class="dropdown-toggle">
                    <i data-count="$count" class="glyphicon glyphicon-bell notification-icon"></i>
                </a>
                <div class="dropdown-container">
                    <div class="dropdown-toolbar">
                        <h3 class="dropdown-toolbar-title">Thông báo (<span class="notif-count">
                            $count
                        </span>)</h3>
                    </div>
                    <div class="notification-container">
                        $listContent
                    </div>
                    <div class="dropdown-footer text-center">
                        <a href="$urlNotifications" target="_blank">Xem tất cả</a>
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
                    var notificationsContent = notificationsWrapper.find('.notification-container')
                    function updateCheckNotification(id) {
                             fetch("$urlApiBase/notifications/" + id, {
                                method: 'PUT',
                                headers: {
                                        'Content-Type': 'application/json',
                                        },
                                }).then(response => {
                                    if (response.ok) {
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1000);
                                    } else {
                                        console.error('Failed to update notification');
                                    }
                                })
                                .catch(error => {
                                    console.error('Fetch error:', error);
                                });
                    }
                    $( document ).ready(function() {
                        var pusher = new Pusher("$appKey", {
                            cluster: 'ap1',
                            encrypted: true
                        });
                        var channel = pusher.subscribe('Notify');
                        channel.bind('send-message', function(data) {
                            data.forEach(function(notification){
                                if (notification.data.user_id == $userId){
                                    notificationsCountElem.attr('data-count', notification.data.count);
                                    notificationsWrapper.find('.notif-count').text(notification.data.count);
                                }
                            });
                        });
                        $('.dropdown-toggle').click(function() {
                            if (notificationsWrapper.hasClass('open')){
                                notificationsWrapper.removeClass('open');
                            } else {
                                $.get("$urlApiBase/notifications/get/$userId", {}, function (data) {
                                    notificationsContent.html(data);
                                    notificationsWrapper.addClass('open');
                                });
                            }
                        })
                    });
                </script>
            HTML;
    }
}
