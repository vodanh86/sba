<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class StatusTransition extends Model
{
    protected $table = 'status_transitions';

	protected $hidden = [
    ];

	protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function nextStatus()
    {
        return $this->belongsTo(Status::class, 'next_status_id');
    }

    public function getViewersAttribute($value)
    {
        return explode(',', $value);
    }

    public function setViewersAttribute($value)
    {
        $this->attributes['viewers'] = implode(',', $value);
    }

    public function getEditorsAttribute($value)
    {
        return explode(',', $value);
    }

    public function setEditorsAttribute($value)
    {
        $this->attributes['editors'] = implode(',', $value);
    }
}
