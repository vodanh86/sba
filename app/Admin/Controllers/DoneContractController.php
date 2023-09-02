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
use Config;

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
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };
        $grid = new Grid(new ContractAcceptance());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'))->filter('like');
        $grid->column('contract.property', __('Tài sản thẩm định giá'))->filter('like');
        $grid->column('date_acceptance', __('Ngày nghiệm thu'))->display($dateFormatter)->width(150)->filter('like');
        $grid->column('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE)->filter('like');
        $grid->column('contract.tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('contract.business_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('contract.personal_address', __('Địa chỉ'))->filter('like');
        $grid->column('contract.representative', __('Người đại diện'))->filter('like');
        $grid->column('contract.position', __('Chức vụ'))->filter('like');
        $grid->column('contract.personal_name', __('Họ và tên'))->filter('like');
        $grid->column('contract.id_number', __('Số CMND/CCCD'))->filter('like');
        $grid->column('contract.issue_place', __('Nơi cấp'))->filter('like');
        $grid->column('contract.issue_date', __('Ngày cấp'))->display($dateFormatter)->filter('like');

        $grid->column('export_bill', __('Xuất hoá đơn'))->display(function ($value) {
            return $value == 0 ? 'Không' : 'Có';
        })->filter('like');
        $grid->column('buyer_name', __('Đơn vị mua'))->filter('like');
        $grid->column('buyer_address', __('Địa chỉ'))->filter('like');
        $grid->column('tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('bill_content', __('Nội dung hoá đơn'))->filter('like');

        $grid->column('total_fee', __('Tổng phí dịch vụ'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150)->filter('like');
        $grid->column('delivery', __('Người chuyển'))->filter('like');
        $grid->column('recipient', __('Người nhận'))->filter('like');
        $grid->column('advance_fee', __('Đã tạm ứng'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150)->filter('like');
        $grid->column('official_fee', __('Còn phải thanh toán'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150)->filter('like');
        $grid->column('document', __('Tài liệu'))->display(function ($urls) {
            $urlsHtml = "";
            foreach($urls as $i => $url){
                $urlsHtml .= "<a href='".env('APP_URL').'/storage/'.$url."' target='_blank'>".basename($url)."</a><br/>";
            }
            return $urlsHtml;        });
        $grid->column('creator.name', __('Người tạo'));
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
        $convertIdToNameUser = function ($tdvId) {
            $adminUser = AdminUser::find($tdvId);
            return $adminUser ? $adminUser->name : '';
        };
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };
        $show = new Show(ContractAcceptance::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('contract.code', __('Mã hợp đồng'));
        $show->field('contract.contract_type', __('Loại hợp đồng'))->using(Constant::CONTRACT_TYPE);
        $show->field('contract.created_date', __('Ngày hợp đồng'))->as($dateFormatter);
        $show->field('contract.customer_type', __('Customer type'))->using(Constant::CUSTOMER_TYPE);
        $show->field('contract.tax_number', __('Mã số thuế'));
        $show->field('contract.business_name', __('Tên doanh nghiệp'));
        $show->field('contract.business_address', __('Địa chỉ'));
        $show->field('contract.representative', __('Người đại diện'));
        $show->field('contract.position', __('Chức vụ'));
        $show->field('contract.personal_address', __('Địa chỉ'));
        $show->field('contract.id_number', __('Số CMND/CCCD'));
        $show->field('contract.personal_name', __('Họ và tên'));
        $show->field('contract.issue_place', __('Nơi cấp'));
        $show->field('contract.issue_date', __('Ngày cấp'))->as($dateFormatter);
        $show->field('contract.property', __('Tài sản thẩm định giá'))->unescape()->as(function ($property) {
            return "<textarea style='width: 100%; height: 200px;' readonly>$property</textarea>";
        });       
        $show->field('contract.purpose', __('Mục đích thẩm định giá'));
        $show->field('contract.appraisal_date', __('Thời điểm thẩm định giá'));
        $show->field('contract.from_date', __('Thời gian thực hiện từ ngày'))->as($dateFormatter);
        $show->field('contract.to_date', __('Đến ngày'))->as($dateFormatter);
        $show->field('contract.total_fee', __('Tổng phí dịch vụ'));
        $show->field('contract.advance_fee', __('Tạm ứng'));
        $show->field('contract.broker', __('Môi giới'));
        $show->field('contract.source', __('Nguồn'));
        $show->field('contract.sale', __('Sale'));
        $show->field('contract.tdv', __('Tdv'))->as($convertIdToNameUser);
        $show->field('contract.legal_representative', __('Đại diện pháp luật'))->as($convertIdToNameUser);
        $show->field('contract.tdv_assistant', __('Trợ lý tdv'))->as($convertIdToNameUser);
        $show->field('supervisorDetail.name', __('Kiểm soát viên'))->as($convertIdToNameUser);
        $show->field('contract.contact', __('Liên hệ'));
        $show->field('contract.note', __('Ghi chú'));
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableDelete();
            });
        return $show;
    }
}
