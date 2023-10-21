<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialAssessment extends Model
{
    protected $table = 'official_assessments';

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

    public function getAssessmentTypeAttribute($value)
    {
        return explode(',', $value);
    }

    public function setAssessmentTypeAttribute($value)
    {
        $this->attributes['assessment_type'] = implode(',', $value);
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
    public function getCertificateDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
    public function getFinishedDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
}
