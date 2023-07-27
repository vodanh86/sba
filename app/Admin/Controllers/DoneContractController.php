<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Status;
use Encore\Admin\Facades\Admin;
use App\Http\Models\AdminUser;
use App\Http\Models\ContractAcceptance;
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
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        };
        $grid = new Grid(new ContractAcceptance());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'));
        $grid->column('date_acceptance', __('Ngày nghiệm thu'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);


        $grid->column('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE);
        $grid->column('contract.tax_number', __('Mã số thuế'));
        $grid->column('contract.business_name', __('Tên doanh nghiệp'));
        $grid->column('contract.personal_address', __('Địa chỉ'));
        $grid->column('contract.representative', __('Người đại diện'));
        $grid->column('contract.position', __('Chức vụ'));
        $grid->column('contract.personal_name', __('Họ và tên'));
        $grid->column('contract.id_number', __('Số CMND/CCCD'));
        $grid->column('contract.issue_place', __('Nơi cấp'));
        $grid->column('contract.issue_date', __('Ngày cấp'));

        $grid->column('export_bill', __('Xuất hoá đơn'))->display(function ($value) {
            return $value == 0 ? 'Không' : 'Có';
        });
        $grid->column('buyer_name', __('Đơn vị mua'));
        $grid->column('buyer_address', __('Địa chỉ'));
        $grid->column('tax_number', __('Mã số thuế'));
        $grid->column('bill_content', __('Nội dung hoá đơn'));

        $grid->column('total_fee', __('Tổng phí dịch vụ'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('delivery', __('Người chuyển'));
        $grid->column('recipient', __('Người nhận'));
        $grid->column('advance_fee', __('Đã tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('official_fee', __('Còn phải thanh toán'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('document', __('Tài liệu'))->display(function ($urls) {
            $urlsHtml = "";
            foreach($urls as $i => $url){
                $urlsHtml .= "<a href='".env('APP_URL').'/storage/'.$url."' target='_blank'>".basename($url)."</a><br/>";
            }
            return $urlsHtml;        });
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);


        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where('status', 35);
        $grid->model()->orderBy('id', 'desc');
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('contract.code', __('Mã hợp đồng'));
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
        $show = new Show(ContractAcceptance::findOrFail($id));

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
