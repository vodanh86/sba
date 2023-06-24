<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationLetter extends Model
{
    protected $table = 'invitation_letters';

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
