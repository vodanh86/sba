<?php

namespace App\Admin\Controllers;

use App\Http\Models\Property;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PropertyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Property';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Property());

        $grid->column('name', __('Name'));
        $grid->column('customer_name', __('Customer name'));
        $grid->column('property_type', __('Property type'));
        $grid->column('address', __('Address'));
        $grid->column('purpose', __('Purpose'));
        $grid->column('ptvt_type', __('Ptvt type'));
        $grid->column('production_year', __('Production year'));
        $grid->column('registration_number', __('Registration number'));
        $grid->column('business', __('Business'));
        $grid->column('branch_id', __('Branch id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
        $grid->actions(function ($actions) {
            if (Admin::user()->can(Constant::VIEW_PROPERTIES)) {
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
        $show = new Show(Property::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('property_type', __('Property type'));
        $show->field('address', __('Address'));
        $show->field('purpose', __('Purpose'));
        $show->field('ptvt_type', __('Ptvt type'));
        $show->field('production_year', __('Production year'));
        $show->field('registration_number', __('Registration number'));
        $show->field('business', __('Business'));
        $show->field('name', __('Name'));
        $show->field('customer_name', __('Customer name'));
        $show->field('branch_id', __('Branch id'));

        if (Admin::user()->can(Constant::VIEW_PROPERTIES)) {
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
        $form = new Form(new Property());

        $form->text('property_type', __('Property type'));
        $form->text('address', __('Address'));
        $form->text('purpose', __('Purpose'));
        $form->text('ptvt_type', __('Ptvt type'));
        $form->text('production_year', __('Production year'));
        $form->text('registration_number', __('Registration number'));
        $form->text('business', __('Business'));
        $form->text('name', __('Name'));
        $form->text('customer_name', __('Customer name'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}
