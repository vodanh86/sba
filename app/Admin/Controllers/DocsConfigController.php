<?php

namespace App\Admin\Controllers;

use App\Http\Models\Branch;
use App\Http\Models\DocsConfig;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DocsConfigController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Cấu hình file docs';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DocsConfig());

        $grid->column('type', __('Loại tài liệu'));
        $grid->column('branch.branch_name', __('Tên chi nhánh'));
        $grid->column('key', __('Khoá'));
        $grid->column('value', __('Giá trị'));
        $grid->column('description', __('Mô tả'));
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
        $show = new Show(DocsConfig::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Loại tài liệu'));
        $show->field('branch.branch_name', __('Tên chi nhánh'));
        $show->field('key', __('Khoá'));
        $show->field('value', __('Giá trị'));
        $show->field('description', __('Mô tả'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $branches = Branch::get()->pluck("branch_name", "id");

        $form = new Form(new DocsConfig());
        $form->text('type', __('Loại tài liệu'));
        $form->select('branch_id', __('Tên chi nhánh'))->options($branches);
        $form->text('key', __('Khoá'));
        $form->text('value', __('Giá trị'));
        $form->textarea('description', __('Mô tả'));

        return $form;
    }
}
