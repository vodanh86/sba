<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Status;
use Encore\Admin\Facades\Admin;
use App\Admin\Actions\Document\AddContractComment;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class DoneContractController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hợp đồng đã hoàn thành';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $doneStatus = Status::where("table", "contracts")->where("done", 1)->first();
        $extractDocument = function ($documents){
            $url = "";
            foreach($documents as $x => $document){
                $url .= "<a href='".env('APP_URL').'/../storage/app/'.$document["document"]."' target='_blank'>".basename($document["document"])."</a><br/>";
            }
            return $url;
        };

        $grid = new Grid(new Contract());

        $grid->column('name', __('Sale phụ trách'))->filter('like');
        $grid->column('code', __('Code'))->filter('like');
        $grid->column('comment', __('Bình luận'))->action(AddContractComment::class)->width(250);
        $grid->column('customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE)->filter(Constant::CUSTOMER_TYPE);
        $grid->column('tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('business_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('representative', __('Người đại diện'))->filter('like');
        $grid->column('position', __('Chức vụ'))->filter('like');
        $grid->column('personal_address', __('Địa chỉ'))->filter('like');
        $grid->column('id_number', __('Số CMND/CCCD'))->filter('like');
        $grid->column('personal_name', __('Họ và tên bên thuê dịch vụ'))->filter('like');
        $grid->column('issue_place', __('Nơi cấp'))->filter('like');
        $grid->column('issue_date', __('Ngày cấp'))->filter('range', 'date');
        $grid->column('buyer_name', __('Đơn vị mua'))->filter('like');
        $grid->column('buyer_address', __('Địa chỉ'))->filter('like');
        $grid->column('buyer_tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('bill_content', __('Nội dung hoá đơn'))->filter('like');
        $grid->column('property_type', __('Loại tài sản'))->using(Constant::PROPRERTY_TYPE)->filter(Constant::PROPRERTY_TYPE);
        $grid->column('property_address', __('Địa điểm tài sản'))->filter('like');
        $grid->column('property_purpose', __('Mục đích sử dụng đất'))->using(Constant::PROPRERTY_PURPOSE)->filter(Constant::PROPRERTY_TYPE);
        $grid->column('vehicle_type', __('Loại phương tiện vận tải'))->using(Constant::VEHICLE_TYPE)->filter(Constant::VEHICLE_TYPE);
        $grid->column('production_year', __('Năm sản xuất'))->filter('range', 'date');
        $grid->column('registration_number', __('Biển kiểm soát/Số đăng ký'))->filter('like');
        $grid->column('company_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('borrower', __('Tên khách nợ'))->filter('like');
        $grid->column('purpose', __('Mục đích'))->using(Constant::INVITATION_PURPOSE)->filter(Constant::INVITATION_PURPOSE);
        $grid->column('extended_purpose', __('Mục đích mở rộng'))->filter('like');
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'))->filter('like');
        $grid->column('from_date', __('Từ ngày'))->filter('like');
        $grid->column('to_date', __('Đến ngày'))->filter('like');
        $grid->column('total_fee', __('Tổng phí'))->filter('like');
        $grid->column('advance_fee', __('Tạm ứng'))->filter('like');
        $grid->column('payment_method', __('Hình thức thanh toán'))->using(Constant::PAYMENT_METHOD)->filter(Constant::PAYMENT_METHOD);
        $grid->column('vat', __('Vat'))->using(Constant::YES_NO);
        $grid->column('broker', __('Người môi giới'))->filter('like');
        $grid->column('officialAssessments',__('Kết quả thẩm định chính thức'))->display($extractDocument);
        $grid->column('valuationDocuments',__('Hồ sơ thẩm định giá'))->display($extractDocument);
        $grid->column('scoreCards',__('Phiếu chấm điểm'))->display($extractDocument);
        $grid->column('contractAcceptances',__('Hợp đồng nghiệm thu'))->display($extractDocument);
        $grid->column('statusDetail.name',__('Trạng thái'))->width(100);
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where('status', $doneStatus->id);
        $grid->model()->orderBy('id', 'desc');
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
            $filter->like('code', 'Mã hợp đồng');
            $filter->like('invitationLetter.code', __('contract.Invitation letter id'));
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
        $show->field('status', __('Status'));
        $show->field('invitation_letter_id', __('Invitation letter id'));
        $show->field('contact', __('Contact'));
        $show->field('note', __('Note'));
        $show->field('name', __('Name'));
        $show->field('code', __('Code'));
        $show->field('comment', __('Comment'));
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
}
