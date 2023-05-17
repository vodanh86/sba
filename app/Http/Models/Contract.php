<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';

    public function invitation_letter()
    {
        return $this->belongsTo(InvitationLetter::class, 'invitation_letter_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function individual_customer()
    {
        return $this->belongsTo(IndividualCustomer::class, 'customer_id');
    }

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }

    public function business_customer()
    {
        return $this->belongsTo(BusinessCustomer::class, 'customer_id');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
