<?php

namespace App\Admin\Controllers;

use App\Http\Models\Property;
use App\Http\Models\Contract;
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
        $grid = new Grid(new Contract());

        $grid->column('property_type', __('Loại tài sản'))->using(Constant::PROPRERTY_TYPE)->filter(Constant::PROPRERTY_TYPE);
        $grid->column('property_address', __('Địa điểm tài sản'))->filter('like');
        $grid->column('property_purpose', __('Mục đích sử dụng đất'))->using(Constant::PROPRERTY_PURPOSE)->filter(Constant::PROPRERTY_TYPE);
        $grid->column('vehicle_type', __('Loại phương tiện vận tải'))->using(Constant::VEHICLE_TYPE)->filter(Constant::VEHICLE_TYPE);
        $grid->column('production_year', __('Năm sản xuất'))->filter('range', 'date');
        $grid->column('registration_number', __('Biển kiểm soát/Số đăng ký'))->filter('like');
        $grid->column('company_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('borrower', __('Tên khách nợ'))->filter('like');
        
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->orderBy('id', 'desc');
        $grid->model()->whereNotNull(["property_type", "property_address", "property_purpose", "vehicle_type", "production_year", "registration_number", "company_name", "borrower"]);
        //$grid->model()
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);
        // callback after save
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('property_address', 'Địa điểm tài sản');
            $filter->like('company_name', __('Tên doanh nghiệp'));
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
        $show = new Show(Contract::findOrFail($id));

        $show->field('property_type', __('Loại tài sản'));
        $show->field('property_address', __('Địa điểm tài sản'));
        $show->field('property_purpose', __('Mục đích sử dụng đất'));
        $show->field('vehicle_type', __('Loại phương tiện vận tải'));
        $show->field('production_year', __('Năm sản xuất'));
        $show->field('registration_number', __('Biển kiểm soát/Số đăng ký'));
        $show->field('company_name', __('Tên doanh nghiệp'));
        $show->field('borrower', __('Tên khách nợ'));
        $show->panel()
        ->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });
        return $show;
    }

}
