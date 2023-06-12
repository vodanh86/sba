<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessCustomer extends Model
{
    protected $table = 'business_customers';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
