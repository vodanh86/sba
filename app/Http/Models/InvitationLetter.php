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

    public function individual_customer()
    {
        return $this->belongsTo(IndividualCustomer::class, 'customer_id');
    }

    public function business_customer()
    {
        return $this->belongsTo(BusinessCustomer::class, 'customer_id');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
