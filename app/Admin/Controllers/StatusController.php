<?php

namespace App\Admin\Controllers;

use App\Http\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StatusController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Status';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Status());

        $grid->column('id', __('Id'));
        $grid->column('table', __('Table'));
        $grid->column('status', __('Status'));
        $grid->column('name', __('Name'));
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
        $show = new Show(Status::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('table', __('Table'));
        $show->field('status', __('Status'));
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
        $form = new Form(new Status());

        $form->text('table', __('Table'));
        $form->text('status', __('Status'));
        $form->text('name', __('Name'));

        return $form;
    }
}
