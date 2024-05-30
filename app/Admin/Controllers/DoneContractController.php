<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExporter;
use App\Admin\Extensions\Export\DataProcessors;
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
        $moneyFormatter = function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        };
        $convertIdToNameUser = function ($tdvId) {
            $adminUser = AdminUser::find($tdvId);
            return $adminUser ? $adminUser->name : '';
        };
        $grid = new Grid(new ContractAcceptance());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.contract_type', __('Loại hợp đồng'))->using(Constant::CONTRACT_TYPE);
        $grid->column('contract.created_date', __('Ngày hợp đồng'))->display($dateFormatter);
        $grid->column('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE);
        $grid->column('contract.tax_number', __('Mã số thuế'));
        $grid->column('contract.business_name', __('Tên doanh nghiệp'));
        $grid->column('contract.business_address', __('Địa chỉ doanh nghiệp'));
        $grid->column('contract.representative', __('Người đại diện'));
        $grid->column('contract.position', __('Chức vụ'));
        $grid->column('contract.personal_address', __('Địa chỉ'));
        $grid->column('contract.id_number', __('Số CMND/CCCD'));
        $grid->column('contract.personal_name', __('Họ và tên'));
        $grid->column('contract.issue_place', __('Nơi cấp'));
        $grid->column('contract.issue_date', __('Ngày cấp'))->display($dateFormatter);
        $grid->column('contract.property', __('Tài sản thẩm định giá'));
        $grid->column('contract.purpose', __('Mục đích thẩm định giá'));
        $grid->column('contract.appraisal_date', __('Thời điểm thẩm định giá'));
        $grid->column('contract.from_date', __('Thời gian thực hiện từ ngày'))->display($dateFormatter);
        $grid->column('contract.to_date', __('Thời gian thực hiện đến ngày'))->display($dateFormatter);
        $grid->column('total_fee', __('Tổng phí dịch vụ'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150)->filter('like');
        $grid->column('advance_fee', __('Đã tạm ứng'))->display($moneyFormatter)->width(150)->filter('like');
        $grid->column('contract.broker', __('Môi giới'));
        $grid->column('contract.source', __('Nguồn'));
        $grid->column('contract.sale', __('Sale'));
        $grid->column('contract.tdv', __('Trưởng phòng nghiệp vụ'))->display($convertIdToNameUser);
        $grid->column('contract.legal_representative', __('Đại diện pháp luật'))->display($convertIdToNameUser);
        $grid->column('contract.tdv_migrate', __('Thẩm định viên'))->display($convertIdToNameUser);
        $grid->column('contract.tdv_assistant', __('Trợ lý thẩm định viên'))->display($convertIdToNameUser);
        $grid->column('contract.supervisor', __('Kiểm soát viên'))->display($convertIdToNameUser);
        $grid->column('contract.net_revenue', __('Doanh thu thuần'))->display($moneyFormatter);
        $grid->column('contract.contact', __('Liên hệ'));
        $grid->column('contract.note', __('Ghi chú'));
        $grid->column('contract.document', __('File đính kèm'))->display(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $grid->column('date_acceptance', __('Ngày nghiệm thu'))->display($dateFormatter)->width(150);
        $grid->column('export_bill', __('Xuất hoá đơn'))->display(function ($value) {
            return $value == 0 ? 'Không' : 'Có';
        })->filter('like');
        $grid->column('buyer_name', __('Đơn vị mua'))->filter('like');
        $grid->column('buyer_address', __('Địa chỉ đơn vị mua'))->filter('like');
        $grid->column('tax_number', __('Mã số thuế đơn vị mua'))->filter('like');
        $grid->column('bill_content', __('Nội dung hoá đơn'))->filter('like');
        $grid->column('delivery', __('Người chuyển'))->filter('like');
        $grid->column('recipient', __('Người nhận'))->filter('like');
        $grid->column('official_fee', __('Còn phải thanh toán'))->display($moneyFormatter)->width(150)->filter('like');
        $grid->column('document', __('Tài liệu'))->display(function ($urls) {
            $urlsHtml = "";
            foreach($urls as $i => $url){
                $urlsHtml .= "<a href='".env('APP_URL').'/storage/'.$url."' target='_blank'>".basename($url)."</a><br/>";
            }
            return $urlsHtml;        });
        $grid->column('contract.creator.name', __('Người tạo'));
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);


        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where('status', 26);
        $grid->model()->orderBy('id', 'desc');
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('code', 'like', "%{$this->input}%");
                });
            }, 'Mã hợp đồng');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('property', 'like', "%{$this->input}%");
                });
            }, 'Tài sản thẩm định giá');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('customer_type', 'like', "%{$this->input}%");
                });
            }, 'Loại khách');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('tax_number', 'like', "%{$this->input}%");
                });
            }, 'Mã số thuế');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('business_name', 'like', "%{$this->input}%");
                });
            }, 'Tên doanh nghiệp');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('personal_address', 'like', "%{$this->input}%");
                });
            }, 'Địa chỉ');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('representative', 'like', "%{$this->input}%");
                });
            }, 'Người đại diện');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('position', 'like', "%{$this->input}%");
                });
            }, 'Chức vụ');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('personal_name', 'like', "%{$this->input}%");
                });
            }, 'Họ và tên');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('id_number', 'like', "%{$this->input}%");
                });
            }, 'Số CMND/CCCD');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('issue_place', 'like', "%{$this->input}%");
                });
            }, 'Nơi cấp');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('issue_date', 'like', "%{$this->input}%");
                });
            }, 'Ngày cấp');
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('net_revenue', 'like', "%{$this->input}%");
                });
            }, 'Doanh thu thuần');
            $filter->between('created_at', 'Ngày tạo')->date();
            $filter->between('updated_at', 'Ngày cập nhật')->date();
        });

        $headings = [
            'Id',
            'Mã hợp đồng',
            'Tài sản thẩm định giá',
            'Ngày hợp đồng',
            'Loại khách',
            'Mã số thuế',
            'Tên doanh nghiệp',
            'Địa chỉ doanh nghiệp',
            'Người đại diện',
            'Chức vụ',
            'Họ và tên',
            'Số CMND/CCCD',
            'Nơi cấp',
            'Ngày cấp',
            'Xuất hoá đơn',
            'Đơn vị mua',
            'Địa chỉ đơn vị mua',
            'Mã số thuế đơn vị mua',
            'Nội dung hoá đơn',
            'Tổng phí dịch vụ',
            'Người chuyển',
            'Người nhận',
            'Đã tạm ứng',
            'Còn phải thanh toán',
            'Doanh thu thuần',
            'Người tạo',
            'Ngày tạo',
            'Ngày cập nhật'
        ];
        $grid->exporter(new ExcelExporter("reports.xlsx", [DataProcessors::class, 'processDoneContractData'], Admin::user()->branch_id, $headings));
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
