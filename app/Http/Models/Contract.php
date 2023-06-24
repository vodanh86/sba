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

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }

    public function officialAssessments()
    {
        return $this->hasMany(OfficialAssessment::class);
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

	protected $hidden = [
    ];

	protected $guarded = [];
}
