<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Document\ApproveIcon;
use App\Http\Models\InvitationLetter;
use App\Http\Models\Property;
use App\Http\Models\Contract;
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
    protected $title = 'Contract';

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

        $grid->column('code', __('Code'));
        $grid->column('invitation_letter.code', __('Mã thư mời'));
        $grid->column('customer_type', __('Customer type'))->using(Constant::CUSTOMER_TYPE);
        $grid->column('individual_customer.name', __('Individual Customer'))->display(function ($customer) {
            return ($this->customer_type == 1) ? $customer : "";
        });
        $grid->column('business_customer.name', __('Business Customer'))->display(function ($customer) {
            return ($this->customer_type == 2) ? $customer : "";
        });

        $grid->column('purpose', __('Purpose'));
        $grid->column('from_date', __('From date'));
        $grid->column('to_date', __('To date'));
        $grid->column('broker', __('Broker'));
        $grid->column('name', __('Name'));
        $grid->column('address', __('Address'));
        $grid->column('tax_number', __('Tax number'));
        $grid->column('bill_content', __('Bill content'));
        $grid->column('property.name', __('Property id'));
        $grid->column('total_fee', __('Total fee'));
        $grid->column('payment_method', __('Payment method'))->using(Constant::PAYMENT_METHOD);
        $grid->column('advance_fee', __('Advance fee'));
        $grid->column('vat', __('Vat'))->using(Constant::YES_NO);
        //$grid->column('status_detail.name', __('Status'));
        $grid->column('status')->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        if (Utils::getCreateRole(Constant::CONTRACT_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
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
        $show = new Show(Contract::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('customer_type', __('Customer type'));
        $show->field('customer_id', __('Customer id'));
        $show->field('purpose', __('Purpose'));
        $show->field('from_date', __('From date'));
        $show->field('to_date', __('To date'));
        $show->field('broker', __('Broker'));
        $show->field('name', __('Name'));
        $show->field('address', __('Address'));
        $show->field('tax_number', __('Tax number'));
        $show->field('bill_content', __('Bill content'));
        $show->field('property_id', __('Property id'));
        $show->field('total_fee', __('Total fee'));
        $show->field('payment_method', __('Payment method'));
        $show->field('advance_fee', __('Advance fee'));
        $show->field('vat', __('Vat'));
        $show->field('branch_id', __('Branch id'));
        $show->field('status', __('Status'));

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

        $form->text('code', __('Code'));
        $form->select('invitation_letter_id', __('Thư mời'))->options(InvitationLetter::where("branch_id", Admin::user()->branch_id)->pluck('code', 'id'));
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->setWidth(2, 2)->load('customer_id', env('APP_URL') . '/api/customers?branch_id=' . Admin::user()->branch_id);
        $form->select('customer_id', __('Customer'))->setWidth(2, 2);
        $form->text('purpose', __('Purpose'));
        $form->date('from_date', __('From date'))->default(date('Y-m-d'));
        $form->date('to_date', __('To date'))->default(date('Y-m-d'));
        $form->text('broker', __('Broker'));
        $form->text('name', __('Name'));
        $form->text('address', __('Address'));
        $form->text('tax_number', __('Tax number'));
        $form->text('bill_content', __('Bill content'));
        $form->select('property_id')->options(Property::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->number('total_fee', __('Total fee'));
        $form->select('payment_method', __('Payment method'))->options(Constant::PAYMENT_METHOD)->setWidth(5, 2);
        $form->number('advance_fee', __('Advance fee'));
        $form->select('vat', __('Vat'))->options(Constant::YES_NO)->setWidth(5, 2);
        $form->file('hspl', __('Hspl'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->select('status', __('Status'))->options($status)->setWidth(5, 2)->required();

        $form->tools(function (Form\Tools $tools) {
            //$tools->disableDelete();
        });

        return $form;
    }
}
