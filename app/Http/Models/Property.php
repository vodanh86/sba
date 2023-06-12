<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $table = 'properties';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
	protected $hidden = [
    ];

	protected $guarded = [];
}
