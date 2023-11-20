<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DocsConfig extends Model
{
    protected $table = 'doc_config';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    protected $hidden = [
    ];

    protected $guarded = [];
}
