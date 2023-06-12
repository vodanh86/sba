<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use App\Http\Models\InvitationLetter;
use App\Admin\Actions\Document\AddContractComment;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ContractController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hợp đồng';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $nextStatuses = array();
        $statuses = StatusTransition::where(["table" => Constant::CONTRACT_TABLE])->where("approvers", 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->whereIn("approve_type", [1, 2])->get();
        foreach($statuses as $key =>$status){
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }

        $viewStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new Contract());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Tên'));
        $grid->column('code', __('Mã hợp đồng'));
        $grid->column('invitationLetter.code', __('contract.Invitation letter id'));
        $grid->column('contact', __('Liên hệ'));
        $grid->column('note', __('Chú ý'));

        $grid->column('status',__('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::CONTRACT_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });
        $grid->column('comment', __('Bình luận'))->action(AddContractComment::class)->width(250);
        $grid->column('created_at', __('Ngày tạo'))->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->width(150);
        // callback after save
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
        $show = new Show(Contract::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Tên'));
        $show->field('code', __('Mã hợp đồng'));
        $show->field('invitation_letter_id', __('contract.Invitation letter id'));
        $show->field('contact', __('Liên hệ'));
        $show->field('note', __('Chú ý'));
        $show->field('statusDetail.name', __('Trạng thái'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Contract());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('contract');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::CONTRACT_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::CONTRACT_TABLE)->whereNull("status_id")->first();
            $status[$nextStatuses->next_status_id] = $nextStatuses->nextStatus->name;
        }
        $form->text('name', __('Tên'));
        $form->text('code', __('Mã hợp đồng'))->required();
        $form->select('invitation_letter_id', __('contract.Invitation letter id'))->options(InvitationLetter::where("branch_id", Admin::user()->branch_id)->pluck('code', 'id'))->required();
        // invitation letter
        $form->text('invitation_code', __('Mã thư mời'))->disable();
        $form->text('customer_type', __('Loại khách hàng'))->disable();
        $form->text('customer_id', __('Id Khách hàng'))->disable();
        $form->text('purpose', __('Mục đích'))->disable();
        $form->text('extended_purpose', __('Mục đích mở rộng'))->disable();
        $form->date('appraisal_date', __('Ngày thẩm định'))->default(date('d-m-Y'))->disable();
        $form->date('from_date', __('Từ ngày'))->default(date('Y-m-d'))->disable();
        $form->date('to_date', __('Đến ngày'))->default(date('Y-m-d'))->disable();
        $form->text('broker', __('Người môi giới'))->disable();
        $form->text('name', __('Tên'))->disable();
        $form->text('address', __('Địa chỉ'))->disable();
        $form->text('tax_number', __('Mã số thuế'))->disable();
        $form->text('bill_content', __('Nội dung thanh toán'))->disable();
        $form->number('total_fee', __('Tổng phí'))->disable();
        $form->text('payment_method', __('Hình thức thanh toán'))->disable();
        $form->number('advance_fee', __('Phí tư vấn'))->disable();
        $form->text('vat', __('Vat'))->disable();
        // end
        $form->text('contact', __('Liên hệ'));
        $form->text('note', __('Chú ý'));
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        $url = env('APP_URL') . '/api/invitation-letter';
        // update file information
        $script = <<<EOT
        $(document).on('change', ".invitation_letter_id", function () {
            $.get("$url",{q : this.value}, function (data) {
                $("#invitation_code").val(data.code);
                $("#customer_type").val(data.customer_type);
                $("#customer_id").val(data.customer_id);
                $("#purpose").val(data.purpose);
                $("#extended_purpose").val(data.extended_purpose);
                $("#appraisal_date").val(data.appraisal_date);
                $("#from_date").val(data.from_date);
                $("#to_date").val(data.to_date);
                $("#name").val(data.name);
                $("#address").val(data.address);  
                $("#tax_number").val(data.tax_number);  
                $("#bill_content").val(data.bill_content);  
                $("#total_fee").val(data.total_fee);  
                $("#payment_method").val(data.payment_method);  
                $("#advance_fee").val(data.advance_fee);  
                $("#vat").val(data.vat);                
            });
        });
        EOT;
        
        Admin::script($script);

        return $form;
    }
}
