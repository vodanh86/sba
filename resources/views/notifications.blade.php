
@foreach ($notifications as $notification)
    <div class="card" style="cursor: pointer;" data-notification-id="`+notification.id+`">
        <a class="card-body" href="$urlBase/`+notification.table+`/`+notification.table_id+`" onclick="updateCheckNotification(`+data.id+`)">
            <div class="media-left">
                <div class="media-object">
                    <img src="$userAvartar" class="img-circle" alt="50x50" style="width: 50px; height: 50px;">
                </div>
            </div>
            <div class="media-body">
                <strong class="notification-title">Gửi từ: `+notification.user_send+`</strong>
                    <p class="notification-desc">`+notification.content+`</p>
                    <div class="notification-meta">
                        <small class="timestamp"></small>
                    </div>
            </div>
        </a>
    </div>
@endforeach
                          