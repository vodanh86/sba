<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationLetter extends Model
{
    protected $table = 'invitation_letters';

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function individualCustomer()
    {
        return $this->belongsTo(IndividualCustomer::class, 'customer_id');
    }

    public function businessCustomer()
    {
        return $this->belongsTo(BusinessCustomer::class, 'customer_id');
    }

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
