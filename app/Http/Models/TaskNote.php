<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    protected $table = 'task_notes';

	protected $hidden = [
    ];

	protected $guarded = [];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function sale()
    {
        return $this->belongsTo(AdminUser::class, 'sale_id');
    }

    public function tdvDetail()
    {
        return $this->belongsTo(AdminUser::class, 'tdv');
    }

    public function tdvAssistantDetail()
    {
        return $this->belongsTo(AdminUser::class, 'tdv_assistant');
    }

    public function controllerDetail()
    {
        return $this->belongsTo(AdminUser::class, 'controller');
    }

    public function statusDetail()
    {
        return $this->belongsTo(Status::class, 'status');
    }
}
