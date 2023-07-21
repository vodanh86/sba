<?php

namespace App\Admin\Controllers;

use App\Http\Models\ContractAcceptance;
use App\Http\Models\Contract;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use App\Admin\Actions\Document\AddContractAcceptanceComment;
use App\Http\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class ContractAcceptanceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hợp đồng nghiệm thu';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        };
        $nextStatuses = Utils::getNextStatuses(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "approvers");
        
        $grid = new Grid(new ContractAcceptance());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'));
        $grid->column('date_acceptance', __('Ngày nghiệm thu'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);
       
    
        $grid->column('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE);
        $grid->column('contract.tax_number', __('Mã số thuế'));
        $grid->column('contract.business_name', __('Tên doanh nghiệp'));
        $grid->column('contract.personal_address', __('Địa chỉ'));
        $grid->column('contract.representative', __('Người đại diện'));
        $grid->column('contract.position', __('Chức vụ'));
        $grid->column('contract.personal_name', __('Họ và tên'));
        $grid->column('contract.id_number', __('Số CMND/CCCD'));
        $grid->column('contract.issue_place', __('Nơi cấp'));
        $grid->column('contract.issue_date', __('Ngày cấp'));

        $grid->column('export_bill', __('Xuất hoá đơn'))->display(function ($value) {
            return $value == 0 ? 'Không' : 'Có';
        });        
        $grid->column('buyer_name', __('Đơn vị mua'));
        $grid->column('buyer_address', __('Địa chỉ'));
        $grid->column('tax_number', __('Mã số thuế'));
        $grid->column('bill_content', __('Nội dung hoá đơn'));

        $grid->column('total_fee', __('Tổng phí dịch vụ'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('delivery', __('Người chuyển'));
        $grid->column('recipient', __('Người nhận'));
        $grid->column('advance_fee', __('Đã tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('official_fee', __('Còn phải thanh toán'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('document', __('Tài liệu'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/public/storage/'.$url."' target='_blank'>".basename($url)."</a>";
        });
        $grid->column('comment', __('Ghi chú'))->action(AddContractAcceptanceComment::class)->width(150);

        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::CONTRACT_ACCEPTANCE_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus) {
            $doneStatus = Status::whereIn("id", $editStatus)->where("done", 1)->get();
            $doneStatusIds = $doneStatus->pluck('id')->toArray();
            if (!in_array($actions->row->status, $editStatus) || in_array($actions->row->status, $doneStatusIds)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });
        
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('contract.code', __('Mã hợp đồng'));
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ContractAcceptance::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('contract_id', __('Mã hợp đồng'));
        $show->field('contract.property', __('Tài sản thẩm định giá'));
        $show->field('date_acceptance', __('Ngày nghiệm thu'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        });

        $show->field('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE);
        $show->field('contract.tax_number', __('Mã số thuế'));
        $show->field('contract.business_name', __('Tên doanh nghiệp'));
        $show->field('contract.personal_address', __('Địa chỉ'));
        $show->field('contract.representative', __('Người đại diện'));
        $show->field('contract.position', __('Chức vụ'));
        $show->field('contract.personal_name', __('Họ và tên'));
        $show->field('contract.id_number', __('Số CMND/CCCD'));
        $show->field('contract.issue_place', __('Nơi cấp'));
        $show->field('contract.issue_date', __('Ngày cấp'));

        $show->field('export_bill', __('Xuất hoá đơn'))->as(function ($value) {
            return $value == 0 ? 'Có' : 'Không';
        });   
        $show->field('buyer_name', __('Đơn vị mua'));
        $show->field('buyer_address', __('Địa chỉ'));
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('bill_content', __('Nội dung hoá đơn'));

        $show->field('total_fee', __('Tổng phí dịch vụ'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $show->field('delivery', __('Người chuyển'));
        $show->field('recipient', __('Người nhận'));
        $show->field('advance_fee', __('Đã tạm ứng'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $show->field('official_fee', __('Còn phải thanh toán'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $show->field('document', __('Tài liệu'))->unescape()->as(function ($url) {
            return "<a href='".env('APP_URL').'/public/storage/'.$url."' target='_blank'>".basename($url)."</a>";
        });
        $show->field('comment', __('Ghi chú'))->action(AddContractAcceptanceComment::class);

        $show->field('status', __('Trạng thái'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableDelete();
            });
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ContractAcceptance());
        $status = array();
        $form->divider('1. Thông tin hợp đồng');
        if ($form->isEditing()) {
            $id = request()->route()->parameter('contract_acceptance');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::CONTRACT_ACCEPTANCE_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach($nextStatuses as $nextStatus){
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::CONTRACT_ACCEPTANCE_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        }
        $form->select('contract_id', __('valuation_document.contract_id'))->options(Contract::where("branch_id", Admin::user()->branch_id)->where('status', Constant::CONTRACT_INPUTTING_STATUS)->pluck('code', 'id'))->required();
        $form->text('property', __('Tài sản thẩm định giá'))->disable();
        $form->date('date_acceptance', __('Ngày nghiệm thu'));

        $form->divider('2. Thông tin khách hàng');
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->disable()->required()->when(1, function (Form $form) {
            $form->text('id_number', __('Số CMND/CCCD'))->disable();
            $form->text('personal_name', __('Họ và tên bên thuê dịch vụ'))->disable();
            $form->text('personal_address', __('Địa chỉ'))->disable();
            $form->date('issue_date', __('Ngày cấp'))->default(date('Y-m-d'))->disable();
            $form->text('issue_place', __('Nơi cấp'))->disable();
        })->when(2, function (Form $form) {
            $form->text('tax_number', __('Mã số thuế'))->disable();
            $form->text('business_name', __('Tên doanh nghiệp'))->disable();
            $form->text('business_address', __('Địa chỉ doanh nghiệp'))->disable();
            $form->text('representative', __('Người đại diện'))->disable();
            $form->text('position', __('Chức vụ'))->disable();
        })->required();


        $form->divider('3. Thông tin xuất hoá đơn');
        $form->select('export_bill', __('Xuất hoá đơn'))->options([0 => 'Có', 1 => 'Không']);
        $form->text('buyer_name', __('Đơn vị mua'));
        $form->text('buyer_address', __('Địa chỉ'));
        $form->text('tax_number', __('Mã số thuế'));
        $form->text('bill_content', __('Nội dung hoá đơn'));

        $form->divider('4. Thông tin phí và thanh toán');
        $form->currency('total_fee', __('Tổng phí'))->symbol('VND');

        $form->text('delivery', __('Người chuyển'));
        $form->text('recipient', __('Người nhận'));
        $form->currency('advance_fee', __('Đã tạm ứng'))->symbol('VND');
        $form->currency('official_fee', __('Còn phải thanh toán'))->symbol('VND');

        $form->divider('5. Thông tin khác');
        $form->file('document', __('Tài liệu'));
        if (in_array("Lưu nháp", $status)) {
            $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp", $status))->setWidth(5, 2)->required();
        } else {
            $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        }
        $form->hidden('branch_id')->default(Admin::user()->branch_id);


        // $url = 'http://127.0.0.1:8000/api/contract';
        $url = env('APP_URL') . '/api/contract';
        
        $script = <<<EOT
        $(function() {
        var contractId = $(".contract_id").val();
        $.get("$url",{q : contractId}, function (data) {
            $("#property").val(data.property);
            $(".customer_type").val(parseInt(data.customer_type)).change();
            $("#tax_number").val(data.tax_number);  
            $("#business_name").val(data.business_name);
            $("#personal_address").val(data.personal_address);
            $("#business_address").val(data.business_address);
            $("#representative").val(data.representative);
            $("#position").val(data.position);
            $("#personal_name").val(data.personal_name);
            $("#id_number").val(data.id_number);  
            $("#issue_place").val(data.issue_place);  
            $("#issue_date").val(data.issue_date); 
        });
        $(document).on('change', ".contract_id", function () {
            $.get("$url",{q : this.value}, function (data) {
                $("#property").val(data.property);
                $(".customer_type").val(parseInt(data.customer_type)).change();
                $("#tax_number").val(data.tax_number);  
                $("#business_name").val(data.business_name);
                $("#personal_address").val(data.personal_address);
                $("#business_address").val(data.business_address);
                $("#representative").val(data.representative);
                $("#position").val(data.position);
                $("#personal_name").val(data.personal_name);
                $("#id_number").val(data.id_number);  
                $("#issue_place").val(data.issue_place);  
                $("#issue_date").val(data.issue_date); 
            });
        });
        });
        EOT;

        Admin::script($script);
        return $form;
    }
}
