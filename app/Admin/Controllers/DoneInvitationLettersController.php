<?php

namespace App\Admin\Controllers;

use App\Http\Models\InvitationLetter;
use App\Admin\Extensions\ExcelExporter;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Status;
use Encore\Admin\Facades\Admin;
use App\Admin\Actions\Document\AddInvitationLetterComment;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

class DoneInvitationLettersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Thư chào phí dịch vụ đã hoàn thành';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $doneStatus = Status::where("table", "invitation_letters")->where("done", 1)->first();
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };
        $grid = new Grid(new InvitationLetter());
        
        $grid->column('id', __('Id'));
        $grid->column('code', __('Mã thư chào'))->filter('like');
        $grid->column('customer_name', __('Tên khách hàng'))->width(150)->filter('like');
        $grid->column('property_type', __('Tài sản thẩm định giá'))->width(150)->filter('like');
        $grid->column('purpose', __('Mục đích thẩm định giá'))->width(150)->filter('like');
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'))->width(150)->filter('like');
        $grid->column('from_date', __('Từ ngày'))->display($dateFormatter)->width(150)->filter('like');
        $grid->column('to_date', __('Đến ngày'))->display($dateFormatter)->width(150)->filter('like');
        $grid->column('total_fee', __('Tổng phí'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150)->filter('like');
        $grid->column('print', __('In thư mời'))->display(function () {
            return "<a class=\"fa fa-print\" href='print-invitation-letter?id=".$this->id."' target='_blank'></a>";
        });
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where('status', $doneStatus->id);
        $grid->model()->orderBy('id', 'desc');
        
        $grid->column('advance_fee', __('Tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        
        
        $grid->column('statusDetail.name',__('Trạng thái'))->width(100);
        $grid->column('comment', __('Bình luận'))->action(AddInvitationLetterComment::class)->width(250);
        $grid->column('userDetail.name', __('Người tạo'));
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);        

        // callback after save
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('code', 'Mã thư chào');
        });
        $grid->exporter(new ExcelExporter("reports.xlsx", InvitationLetter::all()->toArray()));
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
        $show = new Show(InvitationLetter::findOrFail($id));
        $show->field('id', __('Id'));
        $show->field('code', __('Mã thư chào'));
        $show->field('customer_name', __('Tên khách hàng'));
        $show->field('property_type', __('Tài sản thẩm định giá'))->using(Constant::PROPRERTY_TYPE)->width(150);
        $show->field('purpose', __('Mục đích thẩm định giá'))->using(Constant::INVITATION_PURPOSE)->width(150);
        $show->field('appraisal_date', __('Thời điểm thẩm định giá'))->width(150);
        $show->field('from_date', __('Từ ngày'))->width(150);
        $show->field('to_date', __('Đến ngày'))->width(150);
        $show->field('total_fee', __('Tổng phí'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        });
        $show->field('advance_fee', __('Tạm ứng'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        });
        $show->panel()
        ->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });
        return $show;
    }
}
