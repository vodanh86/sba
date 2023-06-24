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
        $grid->column('table', __('Bảng'))->filter(Constant::TABLES);
        $grid->column('status', __('Trạng thái'))->filter();
        $grid->column('name', __('Tên'));
        $grid->column('done', __('Hoàn thiện'));
        $grid->column('created_at', __('Ngày tạo'));
        $grid->column('updated_at', __('Ngày cập nhật'));
        $grid->model()->orderBy('id', 'desc');
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
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        $show->field('table', __('Bảng'));
        $show->field('status', __('Trạng thái'));
        $show->field('name', __('Tên'));

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

        $form->select('table', ('Bảng'))->options(Constant::TABLES);
        $form->text('status', __('Trạng thái'));
        $form->text('name', __('Tên'));
        $form->radio('done', "Hoàn thiện")->options([0 => 'Không', 1 => 'có'])->default(0);

        return $form;
    }
}
