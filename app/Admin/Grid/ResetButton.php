<?php

namespace App\Admin\Grid;

use App\Admin\Controllers\Constant;
use App\Http\Models\Contract;
use Encore\Admin\Actions\RowAction;

class ResetButton extends RowAction
{
    public $name = 'Làm lại';

    public function handle(Contract $contract)
    {
        if ($contract) {
            $resetStatusContract = $contract->contract_type === 0 ? Constant::PRE_CONTRACT_INIT : Constant::OFFICIAL_CONTRACT_INIT; 
            $contract->status = $resetStatusContract;
            $contract->save();
            return $this->response()->success('Reset thành công');
        } else {
            return $this->response()->error('Không thể thực hiện reset cho bản ghi này');
        }
    }
    public function html()
    {
        return "<a class='report-posts btn btn-sm btn-danger'><i class='fa fa-info-circle'></i>Làm lại</a>";
    }
}
