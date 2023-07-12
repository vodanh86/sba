<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Status;
use Encore\Admin\Facades\Admin;
use App\Admin\Actions\Document\AddContractComment;
use App\Http\Models\AdminUser;
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
        $convertIdToNameUser = function ($tdvId) {
            $adminUser = AdminUser::find($tdvId);
            return $adminUser ? $adminUser->name : '';
        };
        $moneyFormatter = function($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        };
        $doneStatus = Status::where("table", "contracts")->where("done", 1)->first();
        $extractDocument = function ($documents){
            $url = "";
            foreach($documents as $x => $document){
                $url .= "<a href='".env('APP_URL').'/public/storage/'.$document["document"]."' target='_blank'>".basename($document["document"])."</a><br/>";
            }
            return $url;
        };

        $grid = new Grid(new Contract());

        $grid->column('id', __('Id'));
        $grid->column('code', __('Mã hợp đồng'));
        $grid->column('contract_type', __('Loại hợp đồng'))->using(Constant::CONTRACT_TYPE)->filter(Constant::CONTRACT_TYPE);
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
        $grid->column('property', __('Tài sản thẩm định giá'))->filter('like');
        $grid->column('purpose', __('Mục đích thẩm định giá'))->filter('like');
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'))->filter('like');
        $grid->column('officialAssessments',__('Kết quả thẩm định chính thức'))->display($extractDocument);
        $grid->column('valuationDocuments',__('Hồ sơ thẩm định giá'))->display($extractDocument);
        $grid->column('scoreCards',__('Phiếu chấm điểm'))->display($extractDocument);
        $grid->column('contractAcceptances',__('Hợp đồng nghiệm thu'))->display($extractDocument);
        $grid->column('from_date', __('Thời gian thực hiện từ ngày'))->filter('like');
        $grid->column('to_date', __('Đến ngày'))->filter('like');
        $grid->column('total_fee', __('Tổng phí dịch vụ'))->display($moneyFormatter);
        $grid->column('advance_fee', __('Tạm ứng'))->display($moneyFormatter);
        $grid->column('broker', __('Môi giới'));
        $grid->column('source', __('Nguồn'));
        $grid->column('sale', __('Sale'));
        $grid->column('tdv', __('Thẩm định viên'))->display($convertIdToNameUser);
        $grid->column('legal_representative', __('Đại diện pháp luật'))->display($convertIdToNameUser);
        $grid->column('assistant.name', __('Trợ lý tdv'));
        $grid->column('supervisor', __('Kiểm soát viên'))->display($convertIdToNameUser);
        $grid->column('contact', __('Liên hệ'))->filter('like');
        $grid->column('note', __('Ghi chú'))->filter('like');
        $grid->column('document', __('File đính kèm'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/public/storage/'.$url."' target='_blank'>".basename($url)."</a>";
        });

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
        $show->field('from_date', __('Thời gian thực hiện từ ngày'));
        $show->field('to_date', __('Đến ngày'));
        $show->field('total_fee', __('Tổng phí dịch vụ'));
        $show->field('advance_fee', __('Tạm ứng'));
        $show->field('broker', __('Môi giới'));
        $show->field('source', __('Nguồn'));
        $show->field('sale', __('Sale'));
        $show->field('tdv', __('Tdv'));
        $show->field('assistant.name', __('Trợ lý tdv'));
        $show->field('supervisorDetail.name', __('Kiểm soát viên'));
        //$show->field('payment_method', __('Hình thức thanh toán'));
        //$show->field('vat', __('Vat'));
        //$show->field('broker', __('Người môi giới'));
        $show->field('contact', __('Liên hệ'));
        $show->field('note', __('Ghi chú'));
        $show->panel()
        ->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });
        return $show;
    }
}
