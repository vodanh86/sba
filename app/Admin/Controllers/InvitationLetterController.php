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
        $grid->column('customer_type', __('Loại khách hàng'))->using(Constant::CUSTOMER_TYPE);
        $grid->column('tax_number', __('Mã số thuế'));
        $grid->column('business_name', __('Tên doanh nghiệp'));
        $grid->column('representative', __('Người đại diện'));
        $grid->column('position', __('Chức vụ'));
        $grid->column('personal_address', __('Địa chỉ'));
        $grid->column('id_number', __('Số CMND/CCCD'));
        $grid->column('personal_name', __('Họ và tên bên thuê dịch vụ'));
        $grid->column('issue_place', __('Nơi cấp'));
        $grid->column('issue_date', __('Ngày cấp'));
        $grid->column('buyer_name', __('Đơn vị mua'));
        $grid->column('buyer_address', __('Địa chỉ'));
        $grid->column('buyer_tax_number', __('Mã số thuế'));
        $grid->column('bill_content', __('Nội dung hoá đơn'));
        $grid->column('property_type', __('Loại tài sản'))->using(Constant::PROPRERTY_TYPE);
        $grid->column('property_address', __('Địa điểm tài sản'));
        $grid->column('property_purpose', __('Mục đích sử dụng đất'))->using(Constant::PROPRERTY_PURPOSE);
        $grid->column('vehicle_type', __('Loại phương tiện vận tải'))->using(Constant::VEHICLE_TYPE);
        $grid->column('production_year', __('Năm sản xuất'));
        $grid->column('registration_number', __('Biển kiểm soát/Số đăng ký'));
        $grid->column('company_name', __('Tên doanh nghiệp'));
        $grid->column('borrower', __('Tên khách nợ'));
        $grid->column('purpose', __('Mục đích'))->using(Constant::INVITATION_PURPOSE);
        $grid->column('extended_purpose', __('Mục đích mở rộng'));
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'));
        $grid->column('from_date', __('Từ ngày'));
        $grid->column('to_date', __('Đến ngày'));
        $grid->column('total_fee', __('Tổng phí'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('advance_fee', __('Tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('payment_method', __('Hình thức thanh toán'))->using(Constant::PAYMENT_METHOD);
        $grid->column('vat', __('Vat'))->using(Constant::YES_NO);
        $grid->column('broker', __('Người môi giới'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', $listStatus);
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::INVITATION_LETTER_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });
        $grid->column('comment', __('Bình luận'))->action(AddInvitationLetterComment::class)->width(250);
        $grid->column('status',__('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail ? $this->statusDetail->name : "";
        })->width(100);
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
        $show->field('code', __('Code'));
        $show->field('customer_type', __('Customer type'));
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('business_name', __('Tên doanh nghiệp'));
        $show->field('representative', __('Người đại diện'));
        $show->field('position', __('Chức vụ'));
        $show->field('personal_address', __('Địa chỉ'));
        $show->field('id_number', __('Số CMND/CCCD'));
        $show->field('personal_name', __('Họ và tên bên thuê dịch vụ'));
        $show->field('issue_place', __('Nơi cấp'));
        $show->field('issue_date', __('Ngày cấp'));
        $show->field('buyer_name', __('Đơn vị mua'));
        $show->field('buyer_address', __('Địa chỉ'));
        $show->field('buyer_tax_number', __('Mã số thuế'));
        $show->field('bill_content', __('Nội dung hoá đơn'));
        $show->field('property_type', __('Loại tài sản'));
        $show->field('property_address', __('Địa điểm tài sản'));
        $show->field('property_purpose', __('Mục đích sử dụng đất'));
        $show->field('vehicle_type', __('Loại phương tiện vận tải'));
        $show->field('production_year', __('Năm sản xuất'));
        $show->field('registration_number', __('Biển kiểm soát/Số đăng ký'));
        $show->field('company_name', __('Tên doanh nghiệp'));
        $show->field('borrower', __('Tên khách nợ'));
        $show->field('purpose', __('Mục đích'));
        $show->field('extended_purpose', __('Mục đích mở rộng'));
        $show->field('appraisal_date', __('Thời điểm thẩm định giá'));
        $show->field('from_date', __('Từ ngày'));
        $show->field('to_date', __('Đến ngày'));
        $show->field('total_fee', __('Tổng phí'));
        $show->field('advance_fee', __('Tạm ứng'));
        $show->field('payment_method', __('Hình thức thanh toán'));
        $show->field('vat', __('Vat'));
        $show->field('broker', __('Người môi giới'));
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
        $form = new Form(new InvitationLetter());
        if ($form->isEditing()) {
            $id = request()->route()->parameter('invitation_letter');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::INVITATION_LETTER_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::INVITATION_LETTER_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->hidden('code')->default(Utils::generateInvitationCode("invitation_letters"));
        }

        $form->divider('1. Thông tin khách hàng');
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->setWidth(2, 2)->default(1)->when(1, function (Form $form) {
            $form->text('id_number', __('Số CMND/CCCD'));
            $form->text('personal_name', __('Họ và tên bên thuê dịch vụ'));
            $form->text('personal_address', __('Địa chỉ'));
            $form->date('issue_date', __('Ngày cấp'))->default(date('Y-m-d'));
            $form->text('issue_place', __('Nơi cấp'));
        })->when(2, function (Form $form) {
            $form->text('tax_number', __('Mã số thuế'));
            $form->text('business_name', __('Tên doanh nghiệp'));
            $form->text('business_address', __('Địa chỉ doanh nghiệp'));
            $form->text('representative', __('Người đại diện'));
            $form->text('position', __('Chức vụ'));
        })->required();
        
        $form->text('buyer_name', __('Đơn vị mua'));
        $form->text('buyer_address', __('Địa chỉ'));
        $form->text('buyer_tax_number', __('Mã số thuế'));
        $form->text('bill_content', __('Nội dung hoá đơn'));

        $form->divider('2. Thông tin về hồ sơ thẩm định giá');
        $form->select('property_type', __('Loại tài sản'))->options(Constant::PROPRERTY_TYPE)->setWidth(5, 2);
        $form->text('property_address', __('Địa điểm tài sản'));
        $form->select('property_purpose', __('Mục đích sử dụng đất'))->options(Constant::PROPRERTY_PURPOSE)->setWidth(5, 2);
        $form->select('vehicle_type', __('Loại phương tiện vận tải'))->options(Constant::VEHICLE_TYPE)->setWidth(5, 2);
        $form->text('production_year', __('Năm sản xuất'));
        $form->text('registration_number', __('Biển kiểm soát/Số đăng ký'));
        $form->text('business', __('Ngành nghề'));
        $form->text('company_name', __('Tên doanh nghiệp'));
        $form->text('borrower', __('Tên khách nợ'));
        $form->select('purpose', __('Mục đích'))->options(Constant::INVITATION_PURPOSE)->setWidth(5, 2);
        $form->text('extended_purpose', __('Mục đích mở rộng'));
        $form->date('appraisal_date', __('Thời điểm thẩm định giá'))->default(date('Y-m-d'));

        $form->divider('3. Thời gian thực hiện');
        $form->date('from_date', __('Từ ngày'))->default(date('Y-m-d'));
        $form->date('to_date', __('Đến ngày'))->default(date('Y-m-d'));

        $form->divider('4. Phí dịch vụ');
        $form->currency('total_fee', __('Tổng phí'))->symbol('VND');
        $form->currency('advance_fee', __('Tạm ứng'))->symbol('VND');
        $form->select('payment_method', __('Hình thức thanh toán'))->options(Constant::PAYMENT_METHOD)->setWidth(5, 2);
        $form->select('vat', __('Vat'))->options(Constant::YES_NO)->setWidth(5, 2);

        $form->divider('5. Thông tin môi giới');
        $form->text('broker', __('Người môi giới'));

        $form->divider('7. Trạng thái thư mời');
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}