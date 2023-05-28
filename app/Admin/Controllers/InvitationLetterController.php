<?php

namespace App\Admin\Controllers;

use App\Http\Models\BusinessCustomer;
use App\Http\Models\IndividualCustomer;
use App\Http\Models\InvitationLetter;
use App\Http\Models\Property;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

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
        $grid = new Grid(new InvitationLetter());

        $grid->column('code', __('Code'));
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
        $grid->column('status', __('Status'))->using(Constant::INVITATION_STATUS);
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
        if (Admin::user()->can(Constant::VIEW_INVITATION_LETTERS)) {
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });
        }

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

        $show->field('code', __('Code'));
        $show->field('customer_type', __('Customer type'));
        $show->field('customer_id', __('Customer id'));
        $show->field('purpose', __('Purpose'));
        $show->field('extended_purpose', __('Extended Purpose'));
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

        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

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

        $form->text('code', __('Code'))->required();
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->setWidth(2, 2)->load('customer_id', env('APP_URL') . '/api/customers?branch_id=' . Admin::user()->branch_id);
        $form->select('customer_id', __('Customer'))->setWidth(2, 2)->when(-1, function (Form $form){
            $form->text('id_number', __('Id number'))->disable();
            $form->text('name', __('Name'))->disable();
            $form->text('address', __('Address'))->disable();
            $form->text('issue_place', __('Issue place'))->disable();
            $form->date('issue_date', __('Issue date'))->default(date('Y-m-d'))->disable();
        })->when(-2, function (Form $form){
            $form->text('tax_number', __('Tax number'))->disable();
            $form->text('company_name', __('Name'))->disable();
            $form->text('company_address', __('Address'))->disable();
            $form->text('representative', __('Representative'))->disable();
            $form->text('position', __('Position'))->disable();
        })->required();
        $form->select('purpose', __('Purpose'))->options(Constant::INVITATION_PURPOSE)->setWidth(5, 2);
        $form->text('extended_purpose', __('Extended Purpose'));
        $form->date('appraisal_date', __('Appraisal Date'))->default(date('d-m-Y'));
        $form->date('from_date', __('From date'))->default(date('Y-m-d'));
        $form->date('to_date', __('To date'))->default(date('Y-m-d'));
        $form->text('broker', __('Broker'));
        $form->text('name', __('Name'));
        $form->text('address', __('Address'));
        $form->text('tax_number', __('Tax number'));
        $form->text('bill_content', __('Bill content'));
        $form->number('total_fee', __('Total fee'));
        $form->select('payment_method', __('Payment method'))->options(Constant::PAYMENT_METHOD)->setWidth(5, 2);
        $form->number('advance_fee', __('Advance fee'));
        $form->select('vat', __('Vat'))->options(Constant::YES_NO)->setWidth(5, 2);
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->select('status', __('Status'))->options(Constant::INVITATION_STATUS)->setWidth(5, 2)->default(1)->required();

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
