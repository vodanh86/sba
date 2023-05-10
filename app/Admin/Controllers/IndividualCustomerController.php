<?php

namespace App\Admin\Controllers;

use App\Http\Models\IndividualCustomer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class IndividualCustomerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Khách hàng cá nhân';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new IndividualCustomer());

        $grid->column('name', __('Name'));
        $grid->column('address', __('Address'));
        $grid->column('id_number', __('Id number'));
        $grid->column('issue_place', __('Issue place'));
        $grid->column('issue_date', __('Issue date'));
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
        $show = new Show(IndividualCustomer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('address', __('Address'));
        $show->field('id_number', __('Id number'));
        $show->field('name', __('Name'));
        $show->field('issue_place', __('Issue place'));
        $show->field('issue_date', __('Issue date'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new IndividualCustomer());

        $form->text('name', __('Name'));
        $form->text('address', __('Address'));
        $form->text('id_number', __('Id number'));
        $form->text('issue_place', __('Issue place'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->date('issue_date', __('Issue date'))->default(date('Y-m-d'));

        return $form;
    }
}
