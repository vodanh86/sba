<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExporter;
use App\Http\Models\ContractAcceptance;
use App\Http\Models\Contract;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use App\Admin\Actions\Document\AddContractAcceptanceComment;
use App\Http\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

class ContractAcceptanceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hợp đồng nghiệm thu';

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
        $nextStatuses = Utils::getNextStatuses(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new ContractAcceptance());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'));
        $grid->column('date_acceptance', __('Ngày nghiệm thu'))->display($dateFormatter)->width(150);

        $grid->column('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE)->filter('like');
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
            return $value == 0 ? 'Có' : 'Không';
        });
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
        })->width(150);
        $grid->column('official_fee', __('Còn phải thanh toán'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('document', __('Tài liệu'))->display(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $grid->column('comment', __('Ghi chú'))->action(AddContractAcceptanceComment::class)->width(150)->filter('like');
        $grid->column('print', __('In nghiệm thu'))->display(function () {
            return "<a class=\"fa fa-print\" href='print-contract-acceptance?id=".$this->id."' target='_blank'></a>";
        });
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::CONTRACT_ACCEPTANCE_TABLE) != Admin::user()->roles[0]->slug) {
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus) {
            $doneStatus = Status::whereIn("id", $editStatus)->where("done", 1)->get();
            $doneStatusIds = $doneStatus->pluck('id')->toArray();
            if (in_array($actions->row->status, $doneStatusIds)) {
                $actions->disableDelete();
            }else if(!in_array($actions->row->status, $editStatus)){
                $actions->disableEdit();
            }
        });

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->where(function ($query) {
                $query->whereHas('contract', function ($query) {
                    $query->where('code', 'like', "%{$this->input}%");
                });
            }, 'Mã hợp đồng');
            $filter->where(function ($query) {
                $dates = explode(' - ', $this->input);
                if (count($dates) == 2) {
                    $startDate = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                    $endDate = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                    $query->whereBetween('date_acceptance', [$startDate, $endDate]);
                }
            }, 'Ngày nghiệm thu')->date();
            $filter->where(function ($query) {
                if ($this->input == '1') {
                    $query->whereHas('contract', function ($query) {
                        $query->where('customer_type', 1);
                    });
                } elseif ($this->input == '2') {
                    $query->whereHas('contract', function ($query) {
                        $query->where('customer_type', 2);
                    });
                }
            }, 'Loại khách')->radio([
                ''  => 'Tất cả',
                '1' => 'Khách hàng cá nhân',
                '2' => 'Khách hàng doanh nghiệp',
            ]);
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
                if ($this->input == 'yes') {
                    $query->where('export_bill', 0);
                } elseif ($this->input == 'no') {
                    $query->where('export_bill', 1);
                }
            }, 'Xuất hoá đơn')->radio([
                ''   => 'Tất cả',
                'yes' => 'Có',
                'no'  => 'Không',
            ]);
            $filter->where(function ($query) {
                $input = str_replace(',', '', $this->input);
                $query->where('advance_fee', '=', $input);
            }, 'Đã tạm ứng');
            $filter->where(function ($query) {
                $input = str_replace(',', '', $this->input);
                $query->where('official_fee', '=', $input);
            }, 'Còn phải thanh toán');
            $filter->where(function ($query) {
                $query->whereHas('statusDetail', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Trạng thái');
            $filter->date('created_at', 'Ngày tạo');
            $filter->date('updated_at', 'Ngày cập nhật');
        });

        $grid->exporter(new ExcelExporter("reports.xlsx", $this->processData()));
        return $grid;
    }
    protected function processData(){
        $processedData = array();
        foreach(ContractAcceptance::all() as $index=>$contractAcceptance){
            $processedData[] = [$contractAcceptance->id, $contractAcceptance->contract->code, $contractAcceptance->contract->property, $contractAcceptance->date_acceptance, $contractAcceptance->contract->customer_type, 
                                $contractAcceptance->contract->tax_number,$contractAcceptance->contract->business_name, $contractAcceptance->contract->personal_address, $contractAcceptance->contract->representative,
                                $contractAcceptance->contract->position, $contractAcceptance->contract->personal_name, $contractAcceptance->contract->id_number, $contractAcceptance->contract->issue_place, $contractAcceptance->contract->issue_date,
                                $contractAcceptance->export_bill, $contractAcceptance->buyer_name, $contractAcceptance->buyer_address, $contractAcceptance->tax_number, $contractAcceptance->bill_content,
                                $contractAcceptance->total_fee, $contractAcceptance->delivery, $contractAcceptance->recipient, $contractAcceptance->advance_fee, $contractAcceptance->official_fee,
                                $contractAcceptance->statusDetail->name, $contractAcceptance->created_at, $contractAcceptance->updated_at
                                ];
        }
        return $processedData;
    }
    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };
        $show = new Show(ContractAcceptance::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('contract_id', __('Mã hợp đồng'));
        $show->field('contract.property', __('Tài sản thẩm định giá'))->unescape()->as(function ($property) {
            return "<textarea style='width: 100%; height: 200px;' readonly>$property</textarea>";
        });
        $show->field('date_acceptance', __('Ngày nghiệm thu'))->as($dateFormatter);

        $show->field('contract.customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE);
        $show->field('contract.tax_number', __('Mã số thuế'));
        $show->field('contract.business_name', __('Tên doanh nghiệp'));
        $show->field('contract.personal_address', __('Địa chỉ'));
        $show->field('contract.representative', __('Người đại diện'));
        $show->field('contract.position', __('Chức vụ'));
        $show->field('contract.personal_name', __('Họ và tên'));
        $show->field('contract.id_number', __('Số CMND/CCCD'));
        $show->field('contract.issue_place', __('Nơi cấp'));
        $show->field('contract.issue_date', __('Ngày cấp'))->as($dateFormatter);

        $show->field('export_bill', __('Xuất hoá đơn'))->as(function ($value) {
            return $value == 0 ? 'Có' : 'Không';
        });
        $show->field('buyer_name', __('Đơn vị mua'));
        $show->field('buyer_address', __('Địa chỉ'));
        $show->field('tax_number', __('Mã số thuế'));
        $show->field('bill_content', __('Nội dung hoá đơn'));

        $show->field('total_fee', __('Tổng phí dịch vụ'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $show->field('delivery', __('Người chuyển'));
        $show->field('recipient', __('Người nhận'));
        $show->field('advance_fee', __('Đã tạm ứng'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $show->field('official_fee', __('Còn phải thanh toán'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $show->field('document', __('Tài liệu'))->unescape()->as(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $show->field('comment', __('Ghi chú'))->action(AddContractAcceptanceComment::class);

        $show->field('status', __('Trạng thái'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
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
        $form = new Form(new ContractAcceptance());
        $status = array();
        $form->divider('1. Thông tin hợp đồng');
        if ($form->isEditing()) {
            $id = request()->route()->parameter('contract_acceptance');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::CONTRACT_ACCEPTANCE_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $contractId = $form->model()->find($id)->getOriginal("contract_id");
            $contractsAll = Contract::where("branch_id", Admin::user()->branch_id)->pluck('code', 'id');
            $form->select('contract_id', __('valuation_document.contract_id'))->options($contractsAll)->default($contractId)->required()->readonly();
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::CONTRACT_ACCEPTANCE_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->select('contract_id', __('valuation_document.contract_id'))->options(Contract::where("branch_id", Admin::user()->branch_id)->where('status', Constant::CONTRACT_INPUTTING_STATUS)->whereHas('scoreCards', function ($query) {
                $query->where('status', 74);
            })->whereDoesntHave('contractAcceptances')
                ->pluck('code', 'id'))->required()
                ->creationRules(['required', "unique:contract_acceptances"])
                ->updateRules(['required', "unique:contract_acceptances,contract_id,{{id}}"]);
        }
        $form->textarea('property', __('Tài sản thẩm định giá'))->disable();
        $form->date('date_acceptance', __('Ngày nghiệm thu'));

        $form->divider('2. Thông tin khách hàng');
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->disable()->required()->when(1, function (Form $form) {
            $form->text('id_number', __('Số CMND/CCCD'))->disable();
            $form->text('personal_name', __('Họ và tên bên thuê dịch vụ'))->disable();
            $form->text('personal_address', __('Địa chỉ'))->disable();
            $form->date('issue_date', __('Ngày cấp'))->default(date('Y-m-d'))->disable();
            $form->text('issue_place', __('Nơi cấp'))->disable();
        })->when(2, function (Form $form) {
            $form->text('tax_number', __('Mã số thuế'))->disable();
            $form->text('business_name', __('Tên doanh nghiệp'))->disable();
            $form->text('business_address', __('Địa chỉ doanh nghiệp'))->disable();
            $form->text('representative', __('Người đại diện'))->disable();
            $form->text('position', __('Chức vụ'))->disable();
        })->required();


        $form->divider('3. Thông tin xuất hoá đơn');
        $form->select('export_bill', __('Xuất hoá đơn'))->options([0 => 'Có', 1 => 'Không']);
        $form->text('buyer_name', __('Đơn vị mua'));
        $form->text('buyer_address', __('Địa chỉ'));
        $form->text('tax_number', __('Mã số thuế'));
        $form->text('bill_content', __('Nội dung hoá đơn'));

        $form->divider('4. Thông tin phí và thanh toán');
        $form->currency('total_fee', __('Tổng phí'))->symbol('VND');
        $form->text('delivery', __('Người chuyển'));
        $form->text('recipient', __('Người nhận'));
        $form->currency('advance_fee', __('Đã tạm ứng'))->symbol('VND');
        $form->currency('official_fee', __('Còn phải thanh toán'))->symbol('VND');
        $form->divider('5. Thông tin khác');
        $form->multipleFile('document', __('Tài liệu'))->removable();
        if (in_array("Lưu nháp", $status)) {
            $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp", $status))->setWidth(5, 2)->required();
        } else {
            $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        }
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->hidden('created_by')->default(Admin::user()->id);


        $url = env('APP_URL') . '/api/contract';

        $script = <<<EOT
        $(function() {
            var contractId = $(".contract_id").val();
            var contract;
            function updateInfor() {
                $(".property").val(contract.property);
                $(".customer_type").val(parseInt(contract.customer_type)).change();
                $("#tax_number").val(contract.tax_number);  
                $("#business_name").val(contract.business_name);
                $("#personal_address").val(contract.personal_address);
                $("#business_address").val(contract.business_address);
                $("#representative").val(contract.representative);
                $("#position").val(contract.position);
                $("#personal_name").val(contract.personal_name);
                $("#id_number").val(contract.id_number);  
                $("#issue_place").val(contract.issue_place);  
                $("#issue_date").val(contract.issue_date); 
                $("#total_fee").val(contract.total_fee); 
                $("#delivery").val(contract.sale); 
                $("#advance_fee").val(contract.advance_fee);
                $("#official_fee").val(contract.official_fee); 

                if(contract.customer_type == 1){
                    $("#delivery").val(''); 
                    $("#delivery").prop("disabled", true);
                    $("#recipient").prop("disabled", true);
                }
                updateBill();
            }
            function updateBill(){
                if($('.export_bill').val() == 0){
                    if (contract) {
                        if(contract.customer_type == 1){
                            $(".buyer_name").val(contract.personal_name);
                            $(".buyer_address").val(contract.personal_address);
                        } else {
                            $(".buyer_name").val(contract.business_name);
                            $(".buyer_address").val(contract.business_address);
                            $(".tax_number").val(contract.tax_number);
                        }
                        $(".bill_content").val('Phí dịch vụ TĐG theo HĐ số ' + contract.code + '/TĐG-SBA');
                    }
                } else {
                    $(".buyer_name").val('');
                    $(".buyer_address").val('');
                    $(".tax_number").val('');
                    $(".bill_content").val('');
                }
            }
            $.get("$url",{q : contractId}, function (data) {
                contract = data;
                updateInfor();
            });
            $(document).on('change', ".contract_id", function () {
                $.get("$url",{q : this.value}, function (data) {
                    contract = data;
                    updateInfor();
                });
            });
            $(document).on('change', ".export_bill", function () {
                updateBill();
            });
        });
        EOT;

        Admin::script($script);
        return $form;
    }
}
