<?php

namespace App\Admin\Controllers;

use App\Http\Models\Branch;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BranchController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Quản lý chi nhánh';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Branch());

        $grid->column('id', __('Id'));
        $grid->column('branch_name', __('Tên chi nhánh'))->filter('like');
        $grid->column('code', __('Mã chi nhánh'))->filter('like');
        $grid->column('address', __('Địa chỉ'))->filter('like');
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
        $show = new Show(Branch::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('branch_name', __('Tên chi nhánh'));
        $show->field('address', __('Địa chỉ'));
        $show->field('code', __('Mã chi nhánh'));
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
        $form = new Form(new Branch());

        $form->text('branch_name', __('Tên chi nhánh'))->required();
        $form->text('code', __('Mã chi nhánh'))->required();
        $form->text('address', __('Địa chỉ'));

        return $form;
    }
}
