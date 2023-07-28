<?php

namespace App\Admin\Controllers;

use App\Http\Models\InvitationLetter;
use App\Admin\Actions\Document\AddInvitationLetterComment;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

class InvitationLetterController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Thư chào phí dịch vụ';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $nextStatuses = array();
        $noneDoneStatus = Status::where("table", "invitation_letters")->where("done", 0)->get();
        $noneDonestatusIds = $noneDoneStatus->pluck('id');
        $statuses = StatusTransition::where(["table" => Constant::INVITATION_LETTER_TABLE])->where("approvers", 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->whereIn("approve_type", [1, 2])->get();
        foreach($statuses as $key =>$status){
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }

        $viewStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::INVITATION_LETTER_TABLE, Admin::user()->roles[0]->slug, "approvers");
        $listStatus = array_merge($viewStatus, $editStatus, $approveStatus);

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
        
        $grid->column('advance_fee', __('Tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);

        $grid->column('status',__('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail ? $this->statusDetail->name : "";
        })->width(100);

        $grid->column('comment', __('Bình luận'))->action(AddInvitationLetterComment::class)->width(250);
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt)->timezone(Config::get('app.timezone'));
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', $noneDonestatusIds)->orderByDesc('id');
        
        // callback after save
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

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $moneyFormatter = function($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        };
        $form = new Form(new InvitationLetter());
        $form->divider('1. Thông tin thư chào');
        if ($form->isEditing()) {
            $id = request()->route()->parameter('invitation_letter');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::INVITATION_LETTER_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->text('code', "Mã thư chào")->readonly();
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::INVITATION_LETTER_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->text('code', "Mã thư chào")->default(Utils::generateInvitationCode("invitation_letters", Admin::user()->branch_id))->readonly()->setWidth(2, 2);
        }

        $form->text('customer_name', __('Tên khách hàng'));
        $form->divider('2. Thông tin về hồ sơ thẩm định giá');
        $form->textarea('property_type', __('Tài sản thẩm định giá'));
        $form->text('purpose', __('Mục đích thẩm định giá'));
        $form->text('property_address', __('Địa điểm tài sản'));
        $form->text('appraisal_date', __('Thời điểm thẩm định giá'));

        $form->divider('3. Thời gian thực hiện');
        $form->date('from_date', __('Từ ngày'))->default(date('Y-m-d'));
        $form->date('to_date', __('Đến ngày'))->default(date('Y-m-d'));

        $form->divider('4. Phí dịch vụ');
        $form->currency('total_fee', __('Tổng phí'))->symbol('VND');
        $form->currency('advance_fee', __('Tạm ứng'))->symbol('VND');
      
        $form->divider('5. Trạng thái thư mời');
        if (in_array("Lưu nháp", $status)) {
            $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp", $status))->setWidth(5, 2)->required();
        } else {
            $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        }
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
      
    }
}