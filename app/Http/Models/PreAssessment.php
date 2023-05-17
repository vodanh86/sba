<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PreAssessment extends Model
{
    protected $table = 'pre_assessments';

	protected $hidden = [
    ];

	protected $guarded = [];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function performerDetail()
    {
        return $this->belongsTo(AdminUser::class, 'performer');
    }

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }
}
