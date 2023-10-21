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

    public function setDocumentAttribute($documents)
    {
        if (is_array($documents)) {
            $this->attributes['document'] = json_encode($documents);
        }
    }

    public function getDocumentAttribute($documents)
    {
        return is_null($documents) ? [] : json_decode($documents, true);
    }
    public function getFinishedDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
}
