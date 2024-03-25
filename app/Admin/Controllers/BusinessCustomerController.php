<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

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
        $grid = new Grid(new Contract());

        $grid->column('tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('business_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('business_address', __('Địa chỉ'))->filter('like');
        $grid->column('representative', __('Người đại diện'))->filter('like');
        $grid->column('position', __('Chức vụ'))->filter('like');
   
        $grid->model()->whereNotNull(["tax_number", "business_name", "business_address", "representative", "position"]);
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where("customer_type", "=", "2")->orderBy('id', 'desc');
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt)->timezone(Config::get('app.timezone'));
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);
        // callback after save
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('business_name', __('Tên doanh nghiệp'));
            $filter->like('tax_number', __('Mã số thuế'));
            $filter->between('created_at', 'Ngày tạo')->date();
            $filter->between('updated_at', 'Ngày cập nhật')->date();
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

        $show->field('id', __('Id'));
    
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('business_name', __('Tên doanh nghiệp'));
        $show->field('business_address', __('Địa chỉ'));
        $show->field('representative', __('Người đại diện'));
        $show->field('position', __('Chức vụ'));
        $show->panel()
        ->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });
        return $show;
    }
}
