<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ContractAcceptance extends Model
{
    protected $table = 'contract_acceptances';

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
