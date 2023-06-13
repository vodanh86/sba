<?php

namespace App\Admin\Controllers;

use App\Http\Models\BusinessCustomer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

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

        $grid->column('name', __('Tên doanh nghiệp'));
        $grid->column('address', __('Địa chỉ'));
        $grid->column('tax_number', __('Mã số thuế'))->width(100);
        $grid->column('representative', __('Người đại diện'));
        $grid->column('position', __('Chức vụ'));
        $grid->column('branch.branch_name', __('Chi nhánh'));
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
        $grid->model()->orderBy('id', 'desc');
        if (Admin::user()->can(Constant::VIEW_CUSTOMERS)) {
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });
        }
        
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('name', __('Tên doanh nghiệp'));
            $filter->like('tax_number', __('Mã số thuế'));
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
        $show = new Show(BusinessCustomer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('branch_id', __('Id Chi nhánh'));
        $show->field('name', __('Tên doanh nghiệp'));
        $show->field('address', __('Địa chỉ'));
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('representative', __('Người đại diện'));
        $show->field('position', __('Chức vụ'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        $show->field('status', __('Trạng thái'));

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

        $form->text('name', __('Tên doanh nghiệp'));
        $form->text('address', __('Địa chỉ'));
        $form->text('tax_number', __('Mã số thuế'));
        $form->text('representative', __('Người đại diện'));
        $form->text('position', __('Chức vụ'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        return $form;
    }
}
