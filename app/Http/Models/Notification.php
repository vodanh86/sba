<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
