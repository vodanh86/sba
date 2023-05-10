<?php

namespace App\Admin\Controllers;

use App\Http\Models\BusinessCustomer;
use Encore\Admin\Controllers\AdminController;
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
    protected $title = 'BusinessCustomer';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BusinessCustomer());

        $grid->column('id', __('Id'));
        $grid->column('address', __('Address'));
        $grid->column('tax_number', __('Tax number'));
        $grid->column('name', __('Name'));
        $grid->column('representative', __('Representative'));
        $grid->column('position', __('Position'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('branch_id', __('Branch id'));
        $grid->column('status', __('Status'));

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

        $form->text('address', __('Address'));
        $form->text('tax_number', __('Tax number'));
        $form->text('name', __('Name'));
        $form->text('representative', __('Representative'));
        $form->text('position', __('Position'));
        $form->number('branch_id', __('Branch id'));
        $form->number('status', __('Status'));

        return $form;
    }
}
