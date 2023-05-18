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
}
