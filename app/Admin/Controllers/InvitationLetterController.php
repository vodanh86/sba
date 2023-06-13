<?php

namespace App\Admin\Controllers;

use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use App\Http\Models\InvitationLetter;
use App\Http\Models\IndividualCustomer;
use App\Http\Models\BusinessCustomer;
use App\Admin\Actions\Document\AddInvitationLetterComment;
use App\Http\Models\Property;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class InvitationLetterController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Thư chào phí dịch vụ';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $nextStatuses = array();
        $statuses = StatusTransition::where(["table" => Constant::INVITATION_LETTER_TABLE])->where("approvers", 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->whereIn("approve_type", [1, 2])->get();
        foreach($statuses as $key =>$status){
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }

        $viewStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new InvitationLetter());

        $grid->column('code', __('Mã thư chào'));
        $grid->column('customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE);
        $grid->column('individualCustomer.name', __('Cá nhân'))->display(function ($customer) {
            return ($this->customer_type == 1) ? $customer : "";
        });
        $grid->column('businessCustomer.name', __('Doanh nghiệp'))->display(function ($customer) {
            return ($this->customer_type == 2) ? $customer : "";
        });
        $grid->column('customer_id', __('invitation_letter.customer_id'))->display(function () {
            return ($this->customer_type == 1) ? (is_null($this->individualCustomer) ? "" : $this->individualCustomer->id_number) : (is_null($this->businessCustomer) ? "" : $this->businessCustomer->tax_number);
        });
        $grid->column('purpose', __('Mục đích'));
        $grid->column('from_date', __('Từ ngày'));
        $grid->column('to_date', __('Đến ngày'));
        $grid->column('broker', __('Người môi giới'));
     
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return is_null($this->statusDetail) ? "" : $this->statusDetail->name;
        });
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status',array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::INVITATION_LETTER_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });
        $grid->column('comment', __('Bình luận'))->action(AddInvitationLetterComment::class)->width(150);
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y H:i:s');
        })->width(100);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y H:i:s');
        })->width(100);
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('customer_id',  __('invitation_letter.customer_id'));
            $filter->like('code', 'Mã thư chào');
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
        $show = new Show(InvitationLetter::findOrFail($id));

        $show->field('code', __('Mã thư chào'));
        $show->field('customer_type', __('Loại khách hàng'));
        $show->field('customer_id', __('invitation_letter.customer_id'));
        $show->field('purpose', __('Mục đích'));
        $show->field('extended_purpose', __('Mục đích mở rộng'));
        $show->field('from_date', __('Từ ngày'));
        $show->field('to_date', __('Đến ngày'));
        $show->field('broker', __('Người môi giới'));
        $show->field('name', __('Tên tài sản'));
        $show->field('address', __('Địa chỉ'));
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('bill_content', __('Nội dung thanh toán'));
        $show->field('property_id', __('invitation_letter.Property id'));
        $show->field('total_fee', __('Tổng phí'));
        $show->field('payment_method', __('Hình thức thanh toán'));
        $show->field('advance_fee', __('Phí tư vấn'));
        $show->field('vat', __('Vat'));
        $show->field('branch_id', __('Id Chi nhánh'));
        $show->field('status', __('Trạng thái'));

        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));

        if (Admin::user()->can(Constant::VIEW_INVITATION_LETTERS)) {
            $show->panel()
                ->tools(function ($tools) {
                    $tools->disableEdit();
                    $tools->disableDelete();
                });
        }

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new InvitationLetter());
        $status = array();
        $customers = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('invitation_letter');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::INVITATION_LETTER_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            if (!is_null($model->statusDetail)) {
                $status[$model->status] = $model->statusDetail->name;
            }
            if ($model->customer_type == 1){
                $customers = IndividualCustomer::where("branch_id", Admin::user()->branch_id)->pluck('id_number', 'id');
            } else {
                $customers = BusinessCustomer::where("branch_id", Admin::user()->branch_id)->pluck('tax_number', 'id');
            }
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::INVITATION_LETTER_TABLE)->whereNull("status_id")->first();
            $status[$nextStatuses->next_status_id] = $nextStatuses->nextStatus->name;
        }
        $form->text('code', __('Mã thư chào'))->required();
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->setWidth(2, 2)->load('customer_id', env('APP_URL') . '/api/customers?branch_id=' . Admin::user()->branch_id);
        $form->select('customer_id', __('invitation_letter.customer_id'))->options($customers)->setWidth(2, 2)->when(-1, function (Form $form) {
            $form->text('id_number', __('Id number'))->disable();
            $form->text('name', __('Tên tài sản'))->disable();
            $form->text('address', __('Địa chỉ'))->disable();
            $form->text('issue_place', __('Địa điểm tài sản'))->disable();
            $form->date('issue_date', __('Ngày phát hành'))->default(date('Y-m-d'))->disable();
        })->when(-2, function (Form $form) {
            $form->text('tax_number', __('Mã số thuế'))->disable();
            $form->text('company_name', __('Tên doanh nghiệp'))->disable();
            $form->text('company_address', __('Địa chỉ'))->disable();
            $form->text('representative', __('Người đại diện'))->disable();
            $form->text('position', __('Chức vụ'))->disable();
        })->required();
        $form->select('property_id', __('invitation_letter.Property id'))->options(Property::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'))->setWidth(5, 2)->required();
        $form->select('purpose', __('Mục đích'))->options(Constant::INVITATION_PURPOSE)->setWidth(5, 2);
        $form->text('extended_purpose', __('Mục đích mở rộng'));
        $form->date('appraisal_date', __('Ngày thẩm định'))->default(date('d-m-Y'));
        $form->date('from_date', __('Từ ngày'))->default(date('Y-m-d'));
        $form->date('to_date', __('Đến ngày'))->default(date('Y-m-d'));
        $form->text('broker', __('Người môi giới'));
        $form->text('name', __('Tên tài sản'));
        $form->text('address', __('Địa chỉ'));
        $form->text('tax_number', __('Mã số thuế'));
        $form->text('bill_content', __('Nội dung thanh toán'));
        $form->number('total_fee', __('Tổng phí'));
        $form->select('payment_method', __('Hình thức thanh toán'))->options(Constant::PAYMENT_METHOD)->setWidth(5, 2);
        $form->number('advance_fee', __('Tạm ứng'));
        $form->select('vat', __('Vat'))->options(Constant::YES_NO)->setWidth(5, 2);
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->select('customer_status', __('Trạng thái khách hàng'))->options(Constant::INVITATION_STATUS)->setWidth(5, 2)->default(1)->required();

        $url = env('APP_URL') . '/api/customer';
        // update file information
        $script = <<<EOT
        $(document).on('change', ".customer_id", function () {
            var type = $(".customer_type").val();
            var other_type = (type == "1") ? "2" : "1";
            $.get("$url",{q : this.value, type: type}, function (data) {
                $("#id_number").val(data.id_number);
                $("#name").val(data.name);
                $("#address").val(data.address);
                $("#issue_place").val(data.issue_place);
                $("#issue_date").val(data.issue_date);
                $("#tax_number").val(data.tax_number);
                $("#company_name").val(data.name);
                $("#company_address").val(data.address);
                $("#representative").val(data.representative);
                $("#position").val(data.position);                
            });
            $(".cascade-customer_id-2d3" + other_type).addClass("hide");
            $(".cascade-customer_id-2d3" + type).removeClass("hide");
        });
        EOT;

        Admin::script($script);

        return $form;
    }
}
