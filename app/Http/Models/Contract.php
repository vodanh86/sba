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

    public function codePreContracts()
    {
        return $this->belongsTo(Contract::class, 'code_pre_contracts');
    }

    public function tdvDetail()
    {
        return $this->belongsTo(AdminUser::class, 'tdv');
    } 
    public function assistant()
    {
        return $this->belongsTo(AdminUser::class, 'tdv_assistant');
    }   

    public function supervisorDetail()
    {
        return $this->belongsTo(AdminUser::class, 'supervisor');
    }
    public function legalRepresentative()
    {
        return $this->belongsTo(AdminUser::class, 'legal_representative');
    }  

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }   

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function officialAssessments()
    {
        return $this->hasMany(OfficialAssessment::class);
    }

    public function preAssessments()
    {
        return $this->hasMany(PreAssessment::class);
    }

    public function valuationDocuments()
    {
        return $this->hasMany(ValuationDocument::class);
    }

    public function scoreCards()
    {
        return $this->hasMany(ScoreCard::class);
    }

    public function contractAcceptances()
    {
        return $this->hasMany(ContractAcceptance::class);
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
    public function getCreatedDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
    public function getIssueDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
    public function getFromDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
    public function getToDateAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }

	protected $hidden = [
    ];

	protected $guarded = [];
}
