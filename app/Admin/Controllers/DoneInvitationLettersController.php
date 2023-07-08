<?php

namespace App\Admin\Controllers;

use App\Http\Models\InvitationLetter;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Status;
use Encore\Admin\Facades\Admin;
use App\Admin\Actions\Document\AddInvitationLetterComment;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

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
        $grid = new Grid(new InvitationLetter());
        
        $grid->column('id', __('Id'));
        $grid->column('code', __('Mã thư chào'));
        $grid->column('customer_name', __('Tên khách hàng'))->width(150);
        $grid->column('property_type', __('Tài sản thẩm định giá'))->width(150);
        $grid->column('purpose', __('Mục đích thẩm định giá'))->width(150);
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'))->width(150);
        $grid->column('from_date', __('Từ ngày'))->width(150);
        $grid->column('to_date', __('Đến ngày'))->width(150);
        $grid->column('total_fee', __('Tổng phí'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where('status', $doneStatus->id);
        $grid->model()->orderBy('id', 'desc');
        
        $grid->column('advance_fee', __('Tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        
        
        $grid->column('statusDetail.name',__('Trạng thái'))->width(100);
        $grid->column('comment', __('Bình luận'))->action(AddInvitationLetterComment::class)->width(250);
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);        

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
