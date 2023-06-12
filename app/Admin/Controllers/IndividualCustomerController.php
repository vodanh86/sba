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

        $grid->column('name', __('Họ và tên bên thuê dịch vụ'));
        $grid->column('address', __('Địa chỉ'));
        $grid->column('id_number', __('Số CMND/CCCD'))->width(150);
        $grid->column('issue_place', __('Nơi cấp'));
        $grid->column('issue_date', __('Ngày cấp'));
        $grid->column('branch.branch_name', __('Chi nhánh'));
        $grid->column('created_at', __('Ngày tạo'));
        $grid->column('updated_at', __('Ngày cập nhật'));
        $grid->model()->orderBy('id', 'desc');
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
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
        $show = new Show(IndividualCustomer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Họ và tên bên thuê dịch vụ'));
        $show->field('address', __('Địa chỉ'));
        $show->field('id_number', __('Số CMND/CCCD'));
        $show->field('issue_place', __('Nơi cấp'));
        $show->field('issue_date', __('Ngày cấp'));
        $show->field('branch.branch_name', __('Chi nhánh'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        if (Admin::user()->can(Constant::VIEW_CUSTOMERS)) {
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
        $form = new Form(new IndividualCustomer());

        $form->text('name', __('Họ và tên bên thuê dịch vụ'));
        $form->text('address', __('Địa chỉ'));
        $form->text('id_number', __('Số CMND/CCCD'));
        $form->text('issue_place', __('Nơi cấp'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->date('issue_date', __('Ngày cấp'))->default(date('Y-m-d'));

        return $form;
    }
}
