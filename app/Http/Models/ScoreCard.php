<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreCard extends Model
{
    protected $table = 'score_cards';

	protected $hidden = [
    ];

	protected $guarded = [];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
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

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'creator_id');
    } 
}
