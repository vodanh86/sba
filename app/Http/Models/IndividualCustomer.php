<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class IndividualCustomer extends Model
{
    protected $table = 'individual_customers';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
	protected $hidden = [
    ];

	protected $guarded = [];
}
