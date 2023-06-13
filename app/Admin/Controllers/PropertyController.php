<?php

namespace App\Admin\Controllers;

use App\Http\Models\Property;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class PropertyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Tài sản thẩm định';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Property());

        $grid->column('name', __('Tên tài sản'))->width(200);
        $grid->column('customer_name', __('Tên khách/doanh nghiệp'))->width(250);
        $grid->column('property_type', __('Loại tài sản'))->using(Constant::PROPRERTY_TYPE)->width(150);
        $grid->column('address', __('Địa điểm tài sản'))->using(Constant::PROPRERTY_ADDRESS)->width(200);
        $grid->column('purpose', __('Mục đích sử dụng đất'))->using(Constant::PROPRERTY_PURPOSE)->width(150);
        $grid->column('ptvt_type', __('Loại hình PTVT'))->using(Constant::VEHICLE_TYPE)->width(150);
        $grid->column('production_year', __('Năm sản xuất'))->width(150);
        $grid->column('registration_number', __('Biển kiểm soát/Số đăng ký'))->width(150);
        $grid->column('business', __('Ngành nghề'))->width(150);
        $grid->column('branch.branch_name', __('Chi nhánh'))->width(150);
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
        if (Admin::user()->can(Constant::VIEW_PROPERTIES)) {
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });
        }
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('customer_name', __('Tên khách/doanh nghiệp'));
            $filter->like('name', __('Tên tài sản'));
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
        $show->field('branch_id', __('Id Chi nhánh'));
        $show->field('name', __('Tên tài sản'));
        $show->field('customer_name', __('Tên khách/doanh nghiệp'));
        $show->field('property_type', __('Loại tài sản'));
        $show->field('address', __('Địa điểm'));
        $show->field('purpose', __('Mục đích sử dụng đất'));
        $show->field('ptvt_type', __('Loại PTVT'));
        $show->field('production_year', __('Năm sản xuất'));
        $show->field('registration_number', __('Biển kiểm soát/Số đăng ký'));
        $show->field('business', __('Ngành nghề'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));

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

        $form->text('customer_name', __('Tên khách/doanh nghiệp'));
        $form->text('name', __('Tên tài sản'))->required();
        $form->select('property_type', __('Loại tài sản'))->options(Constant::PROPRERTY_TYPE)->setWidth(5, 2);
        $form->text('address', __('Địa điểm'));
        $form->select('purpose', __('Mục đích sử dụng đất'))->options(Constant::PROPRERTY_PURPOSE)->setWidth(5, 2);
        $form->select('ptvt_type', __('Loại PTVT'))->options(Constant::VEHICLE_TYPE)->setWidth(5, 2);
        $form->text('production_year', __('Năm sản xuất'));
        $form->text('registration_number', __('Biển kiểm soát/Số đăng ký'));
        $form->text('business', __('Ngành nghề'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}
