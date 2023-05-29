<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';

    public function invitationLetter()
    {
        return $this->belongsTo(InvitationLetter::class, 'invitation_letter_id');
    }

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
