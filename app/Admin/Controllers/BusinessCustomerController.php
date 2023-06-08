<?php

namespace App\Admin\Controllers;

use App\Http\Models\BusinessCustomer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BusinessCustomerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Khách hàng doanh nghiệp';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BusinessCustomer());

        $grid->column('name', __('Name'));
        $grid->column('address', __('Address'));
        $grid->column('tax_number', __('Tax number'));
        $grid->column('representative', __('Representative'));
        $grid->column('position', __('Position'));
        $grid->column('branch.branch_name', __('Branch'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
        $grid->model()->orderBy('id', 'desc');
        if (Admin::user()->can(Constant::VIEW_CUSTOMERS)) {
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
        $show = new Show(BusinessCustomer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('address', __('Address'));
        $show->field('tax_number', __('Tax number'));
        $show->field('name', __('Name'));
        $show->field('representative', __('Representative'));
        $show->field('position', __('Position'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('branch_id', __('Branch id'));
        $show->field('status', __('Status'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BusinessCustomer());

        $form->text('name', __('Name'));
        $form->text('address', __('Address'));
        $form->text('tax_number', __('Tax number'));
        $form->text('representative', __('Representative'));
        $form->text('position', __('Position'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        return $form;
    }
}
