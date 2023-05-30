<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use App\Http\Models\InvitationLetter;
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
        $grid = new Grid(new Contract());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('code', __('Code'));
        $grid->column('invitationLetter.name', __('contract.Invitation letter id'));
        $grid->column('invitationLetter.code', __('Code'));
        $grid->column('contact', __('Contact'));
        $grid->column('note', __('Note'));

        $grid->column('statusDetail.name', __('Status'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
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
        $show->field('code', __('Code'));
        $show->field('invitation_letter_id', __('contract.Invitation letter id'));
        $show->field('contact', __('Contact'));
        $show->field('note', __('Note'));
        $show->field('statusDetail.name', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));

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
        $form->text('name', __('Name'));
        $form->text('code', __('Code'));
        $form->select('invitation_letter_id', __('contract.Invitation letter id'))->options(InvitationLetter::where("branch_id", Admin::user()->branch_id)->pluck('code', 'id'))->required();
        // invitation letter
        $form->text('code', __('Code'))->disable();
        $form->text('customer_type', __('Loại khách hàng'))->disable();
        $form->text('customer_id', __('Customer'))->disable();
        $form->text('purpose', __('Purpose'))->disable();
        $form->text('extended_purpose', __('Extended Purpose'))->disable();
        $form->date('appraisal_date', __('Appraisal Date'))->default(date('d-m-Y'))->disable();
        $form->date('from_date', __('From date'))->default(date('Y-m-d'))->disable();
        $form->date('to_date', __('To date'))->default(date('Y-m-d'))->disable();
        $form->text('broker', __('Broker'))->disable();
        $form->text('name', __('Name'))->disable();
        $form->text('address', __('Address'))->disable();
        $form->text('tax_number', __('Tax number'))->disable();
        $form->text('bill_content', __('Bill content'))->disable();
        $form->number('total_fee', __('Total fee'))->disable();
        $form->text('payment_method', __('Payment method'))->disable();
        $form->number('advance_fee', __('Advance fee'))->disable();
        $form->text('vat', __('Vat'))->disable();
        // end
        $form->text('contact', __('Contact'));
        $form->text('note', __('Note'));
        $form->select('status', __('Status'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        $url = env('APP_URL') . '/api/invitation-letter';
        // update file information
        $script = <<<EOT
        $(document).on('change', ".invitation_letter_id", function () {
            $.get("$url",{q : this.value}, function (data) {
                $("#code").val(data.code);
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
