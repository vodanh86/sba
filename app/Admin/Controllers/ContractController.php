<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use App\Http\Models\InvitationLetter;
use App\Admin\Actions\Document\AddContractComment;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class ContractController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hợp đồng';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $nextStatuses = array();
        $statuses = StatusTransition::where(["table" => Constant::CONTRACT_TABLE])->where("approvers", 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->whereIn("approve_type", [1, 2])->get();
        foreach($statuses as $key =>$status){
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }

        $viewStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_TABLE, Admin::user()->roles[0]->slug, "approvers");
        $doneStatus = Status::where("table", "contracts")->where("done", 1)->first();
        $listStatus = array_merge($viewStatus, $editStatus, $approveStatus);
        if (($key = array_search($doneStatus->id, $listStatus)) !== false) {
            unset($listStatus[$key]);
        }

        $grid = new Grid(new Contract());

        $grid->column('id', __('Id'));
        $grid->column('code', __('Mã hợp đồng'));
        $grid->column('contract_type', __('Loại hợp đồng'))->using(Constant::CONTRACT_TYPE)->filter(Constant::CONTRACT_TYPE);
        //$grid->column('invitationLetter.code', __('contract.Invitation letter id'));
        $grid->column('created_date', __('Ngày hợp đồng'));
        $grid->column('customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE)->filter(Constant::CUSTOMER_TYPE);
        $grid->column('tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('business_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('business_address', __('Địa chỉ'))->filter('like');
        $grid->column('representative', __('Người đại diện'))->filter('like');
        $grid->column('position', __('Chức vụ'))->filter('like');
        $grid->column('personal_address', __('Địa chỉ'))->filter('like');
        $grid->column('id_number', __('Số CMND/CCCD'))->filter('like');
        $grid->column('personal_name', __('Họ và tên'))->filter('like');
        $grid->column('issue_place', __('Nơi cấp'))->filter('like');
        $grid->column('issue_date', __('Ngày cấp'))->filter('like');
        //$grid->column('buyer_name', __('Đơn vị mua'));
        //$grid->column('buyer_address', __('Địa chỉ'));
        //$grid->column('buyer_tax_number', __('Mã số thuế'));
        //$grid->column('bill_content', __('Nội dung hoá đơn'));
        $grid->column('property', __('Tài sản thẩm định giá'))->filter('like');
        //$grid->column('property_type', __('Loại tài sản'))->using(Constant::PROPRERTY_TYPE);
        //$grid->column('property_address', __('Địa điểm tài sản'));
        //$grid->column('property_purpose', __('Mục đích sử dụng đất'))->using(Constant::PROPRERTY_PURPOSE);
        //$grid->column('vehicle_type', __('Loại phương tiện vận tải'))->using(Constant::VEHICLE_TYPE);
        //$grid->column('production_year', __('Năm sản xuất'));
        //$grid->column('registration_number', __('Biển kiểm soát/Số đăng ký'));
        //$grid->column('company_name', __('Tên doanh nghiệp'));
        //$grid->column('borrower', __('Tên khách nợ'));
        $grid->column('purpose', __('Mục đích thẩm định giá'))->filter('like');
        //$grid->column('purpose', __('Mục đích'))->using(Constant::INVITATION_PURPOSE);
        //$grid->column('extended_purpose', __('Mục đích mở rộng'));
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'))->filter('like');
        $grid->column('from_date', __('Thời gian thẩm định'))->filter('like');
        
        $grid->column('total_fee', __('Tổng phí dịch vụ'));
        $grid->column('advance_fee', __('Tạm ứng'));

        $grid->column('broker', __('Môi giới'));
        $grid->column('source', __('Nguồn'));
        $grid->column('sale', __('Sale'));
        $grid->column('tdv', __('Tdv'));
        $grid->column('tdv_assistant', __('Trợ lý tdv'));
        $grid->column('supervisor', __('Kiểm soát viên'));

        $grid->column('contact', __('Liên hệ'))->filter('like');
        $grid->column('note', __('Ghi chú'))->filter('like');
        $grid->column('document', __('File đính kèm'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</a>";
        });
        //$grid->column('payment_method', __('Hình thức thanh toán'))->using(Constant::PAYMENT_METHOD);
        //$grid->column('vat', __('Vat'))->using(Constant::YES_NO);
        

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', $listStatus);
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::CONTRACT_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });
        $grid->column('comment', __('Bình luận'))->action(AddContractComment::class)->width(250);
        $grid->column('status',__('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail ? $this->statusDetail->name : "";
        })->width(100);
        //$grid->column('name', __('Sale phụ trách'));
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('code', 'Mã hợp đồng');
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
        $show->field('code', __('Mã hợp đồng'));
        $show->field('contract_type', __('Loại hợp đồng'))->using(Constant::CONTRACT_TYPE);
        $show->field('created_date', __('Ngày hợp đồng'));
        //$show->field('invitation_letter_id', __('Invitation letter id'));
        //$show->field('name', __('Name'));
        //$show->field('comment', __('Comment'));
        $show->field('customer_type', __('Customer type'))->using(Constant::CUSTOMER_TYPE);
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('business_name', __('Tên doanh nghiệp'));
        $show->field('business_address', __('Địa chỉ'));
        $show->field('representative', __('Người đại diện'));
        $show->field('position', __('Chức vụ'));
        $show->field('personal_address', __('Địa chỉ'));
        $show->field('id_number', __('Số CMND/CCCD'));
        $show->field('personal_name', __('Họ và tên'));
        $show->field('issue_place', __('Nơi cấp'));
        $show->field('issue_date', __('Ngày cấp'));
        //$show->field('buyer_name', __('Đơn vị mua'));
        //$show->field('buyer_address', __('Địa chỉ'));
        //$show->field('buyer_tax_number', __('Mã số thuế'));
        //$show->field('bill_content', __('Nội dung hoá đơn'));
        $show->field('property', __('Tài sản thẩm định giá'));
        //$show->field('property_type', __('Loại tài sản'));
        //$show->field('property_address', __('Địa điểm tài sản'));
        //$show->field('property_purpose', __('Mục đích sử dụng đất'));
        //$show->field('vehicle_type', __('Loại phương tiện vận tải'));
        //$show->field('production_year', __('Năm sản xuất'));
        //$show->field('registration_number', __('Biển kiểm soát/Số đăng ký'));
        //$show->field('company_name', __('Tên doanh nghiệp'));
        //$show->field('borrower', __('Tên khách nợ'));
        $show->field('purpose', __('Mục đích thẩm định giá'));
        //$show->field('extended_purpose', __('Mục đích mở rộng'));
        $show->field('appraisal_date', __('Thời điểm thẩm định giá'));
        $show->field('from_date', __('Thời gian thực hiện'));
        //$show->field('to_date', __('Đến ngày'));
        $show->field('total_fee', __('Tổng phí dịch vụ'));
        $show->field('advance_fee', __('Tạm ứng'));
        //$show->field('payment_method', __('Hình thức thanh toán'));
        //$show->field('vat', __('Vat'));
        $show->field('broker', __('Môi giới'));
        $show->field('source', __('Nguồn'));
        $show->field('sale', __('Sale'));
        $show->field('tdv', __('Tdv'));
        $show->field('tdv_assistant', __('Trợ lý tdv'));
        $show->field('supervisor', __('Kiểm soát viên'));
        $show->field('contact', __('Liên hệ'));
        $show->field('note', __('Ghi chú'));
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
        $form = new Form(new Contract());
        $form->divider('1. Thông tin hợp đồng');
        if ($form->isEditing()) {
            $id = request()->route()->parameter('contract');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::CONTRACT_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->text('code', "Mã hợp đồng")->readonly();
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::CONTRACT_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->text('code', "Mã hợp đồng")->default(Utils::generateCode("contracts", Admin::user()->branch_id))->readonly()->setWidth(2, 2);
        }
        //$form->select('invitation_letter_id', __('contract.Invitation letter id'))->options(InvitationLetter::where("branch_id", Admin::user()->branch_id)->pluck('code', 'id'))->setWidth(2, 2);
        //$form->text('name', __('Sale phụ trách'));
        $form->select('contract_type', __('Loại hợp đồng'))->options(Constant::CONTRACT_TYPE)->setWidth(5, 2)->required();
        $form->date('created_date', __('Ngày hợp đồng'))->default(date('Y-m-d'))->required();

        $form->divider('2. Thông tin khách hàng');
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->setWidth(2, 2)->required()->default(1)->when(1, function (Form $form) {
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
        
        //$form->text('buyer_name', __('Đơn vị mua'));
        //$form->text('buyer_address', __('Địa chỉ'));
        //$form->text('buyer_tax_number', __('Mã số thuế'));
        //$form->text('bill_content', __('Nội dung hoá đơn'));

        $form->divider('3. Thông tin về hồ sơ thẩm định giá');
        $form->text('property', __('Tài sản thẩm định giá'))->required();
        //$form->select('property_type', __('Loại tài sản'))->options(Constant::PROPRERTY_TYPE)->setWidth(5, 2);
        //$form->text('property_address', __('Địa điểm tài sản'));
        //$form->select('property_purpose', __('Mục đích sử dụng đất'))->options(Constant::PROPRERTY_PURPOSE)->setWidth(5, 2);
        //$form->select('vehicle_type', __('Loại phương tiện vận tải'))->options(Constant::VEHICLE_TYPE)->setWidth(5, 2);
        //$form->text('production_year', __('Năm sản xuất'));
        //$form->text('registration_number', __('Biển kiểm soát/Số đăng ký'));
        //$form->text('business', __('Ngành nghề'));
        //$form->text('company_name', __('Tên doanh nghiệp'));
        //$form->text('borrower', __('Tên khách nợ'));
        //$form->select('purpose', __('Mục đích'))->options(Constant::INVITATION_PURPOSE)->setWidth(5, 2);
        $form->text('purpose', __('Mục đích thẩm định giá'))->required();
        $form->date('appraisal_date', __('Thời điểm thẩm định giá'))->default(date('Y-m-d'))->required();
        $form->date('from_date', __('Thời điểm thực hiện'))->default(date('Y-m-d'))->required();
        //$form->divider('4. Thời gian thực hiện');
        //$form->date('from_date', __('Từ ngày'))->default(date('Y-m-d'));
        //$form->date('to_date', __('Đến ngày'))->default(date('Y-m-d'));

        $form->divider('4. Thông tin phí và thanh toán');
        $form->currency('total_fee', __('Tổng phí dịch vụ'))->symbol('VND');
        $form->currency('advance_fee', __('Tạm ứng'))->symbol('VND');
        //$form->select('payment_method', __('Hình thức thanh toán'))->options(Constant::PAYMENT_METHOD)->setWidth(5, 2);
        //$form->select('vat', __('Vat'))->options(Constant::YES_NO)->setWidth(5, 2);
        $form->divider('5. Thông tin phiếu giao việc');
        $form->text('broker', __('Môi giới'))->required();
        $form->text('source', __('Nguồn'));
        $form->text('sale', __('Sale'));
        $form->text('tdv', __('Tdv'));
        $form->text('tdv_assistant', __('Trợ lý tdv'));
        $form->text('supervisor', __('Kiểm soát viên'));
        $form->divider('6. Thông tin khác');
        //$form->text('broker', __('Người môi giới'));
        $form->text('contact', __('liên hệ'));
        $form->text('note', __('Ghi chú'));
        //$form->divider('7. Trạng thái hợp đồng');
        $form->file('document', __('File đính kèm'));
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}