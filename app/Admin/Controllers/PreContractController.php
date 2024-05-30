<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Export\DataProcessors;
use App\Http\Models\Contract;
use App\Http\Models\DocsConfig;
use Encore\Admin\Layout\Content;
use App\Admin\Extensions\ExcelExporter;
use App\Admin\Actions\Document\AddContractComment;
use App\Admin\Grid\ResetButton;
use App\Http\Models\AdminUser;
use App\Http\Models\PreAssessment;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;
use DB;

class PreContractController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Yêu cầu sơ bộ khảo sát';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return $this->search(0);
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function assignedContracts(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($this->search(1));
    }

    protected function search($condition)
    {
        $userNameIds = AdminUser::pluck('name', 'id')->toArray();
        $convertIdToNameUser = function ($tdvId) use ($userNameIds) {
            if (in_array($tdvId, $userNameIds) == 1) {
                return $userNameIds[$tdvId];
            }
            return "";
        };
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };
        $moneyFormatter = function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        };
        $nextStatuses = array();
        $statuses = StatusTransition::whereIn("table", [Constant::CONTRACT_TABLE, Constant::PRE_CONTRACT_TABLE])->where("approvers", 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->whereIn("approve_type", [1, 2])->get();
        foreach ($statuses as $key => $status) {
            $nextStatuses[$status->status_id] = Status::find($status->status_id)->name;
            $nextStatuses[$status->next_status_id] = Status::find($status->next_status_id)->name;
        }

        $viewStatus = Utils::getAvailbleStatusInTables([Constant::CONTRACT_TABLE, Constant::PRE_CONTRACT_TABLE], Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatusInTables([Constant::CONTRACT_TABLE, Constant::PRE_CONTRACT_TABLE], Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatusInTables([Constant::CONTRACT_TABLE, Constant::PRE_CONTRACT_TABLE], Admin::user()->roles[0]->slug, "approvers");
        $doneStatus = Status::where("table", "contracts")->where("done", 1)->first();
        $listStatus = array_merge($viewStatus, $editStatus, $approveStatus);
        if (($key = array_search($doneStatus->id, $listStatus)) !== false) {
            unset($listStatus[$key]);
        }

        $grid = new Grid(new Contract());

        $grid->column('id', __('Id'));
        $grid->column('code', __('Mã yêu cầu SBKS'))->filter('like');
        $grid->column('created_date', __('Ngày hợp đồng'))->display($dateFormatter);
        $grid->column('customer_type', __('Loại khách'))->using(Constant::CUSTOMER_TYPE)->filter(Constant::CUSTOMER_TYPE);
        $grid->column('tax_number', __('Mã số thuế'))->filter('like');
        $grid->column('business_name', __('Tên doanh nghiệp'))->filter('like');
        $grid->column('business_address', __('Địa chỉ'))->filter('like');
        $grid->column('representative', __('Người đại diện'))->filter('like');
        $grid->column('position', __('Chức vụ'))->filter('like');
        $grid->column('personal_address', __('Địa chỉ'))->filter('like');
        $grid->column('print', __('In hợp đồng'))->display(function () {
            return "<a class=\"fa fa-print\" href='print-contract?id=" . $this->id . "' target='_blank'></a>";
        });
        $grid->column('id_number', __('Số CMND/CCCD'))->filter('like');
        $grid->column('personal_name', __('Họ và tên'))->filter('like');
        $grid->column('issue_place', __('Nơi cấp'))->filter('like');
        $grid->column('issue_date', __('Ngày cấp'))->display($dateFormatter);
        $grid->column('property', __('Tài sản thẩm định giá'))->filter('like');
        $grid->column('purpose', __('Mục đích thẩm định giá'))->filter('like');
        $grid->column('appraisal_date', __('Thời điểm thẩm định giá'))->filter('like');
        $grid->column('from_date', __('Thời gian thực hiện từ ngày'))->display($dateFormatter);
        $grid->column('to_date', __('Thời gian thực hiện đến ngày'))->display($dateFormatter);

        $grid->column('total_fee', __('Tổng phí dịch vụ'))->display($moneyFormatter);
        $grid->column('type_fees', __('Loại biểu phí'))->display(function ($typeFees) {
            return $typeFees == 0 ? "Trong biểu phí" : "Ngoài biểu phí";
        });
        $grid->column('advance_fee', __('Tạm ứng'))->display($moneyFormatter);

        $grid->column('broker', __('Môi giới'))->filter('like');
        $grid->column('source', __('Nguồn'))->filter('like');
        $grid->column('sale', __('Sale'))->filter('like');
        $grid->column('tdv', __('Trưởng phòng nghiệp vụ'))->display($convertIdToNameUser);
        $grid->column('legal_representative', __('Đại diện pháp luật'))->display($convertIdToNameUser);
        $grid->column('tdv_migrate', __('Thẩm định viên'))->display($convertIdToNameUser);
        $grid->column('assistant.name', __('Trợ lý thẩm định viên'));
        $grid->column('supervisor', __('Kiểm soát viên'))->display($convertIdToNameUser);
        $grid->column('net_revenue', __('Doanh thu thuần'))->display($moneyFormatter);
        $grid->column('contact', __('Liên hệ'))->filter('like');
        $grid->column('note', __('Ghi chú'))->filter('like');
        $grid->column('document', __('File đính kèm'))->display(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });

        // get list of assigned contracts
        if ($condition == 0) {
            $grid->model()->whereIn('status', $listStatus);
        } else if ($condition == 1) {
            $grid->model()->whereIn('status', [
                Constant::CONTRACT_INPUTTING_STATUS,
                Constant::PRE_CONTRACT_INPUTTING_STATUS,
                Constant::WAIT_ASSIGN,
                Constant::OFFICIAL_ASSIGN,
                Constant::WAIT_CONTRACT_APPROVED,
                Constant::WAIT_TPNV_PRE_CONTRACT_APPROVED
            ]);
            $grid->model()->where(function ($query) {
                $query->where('tdv_assistant', '=', Admin::user()->id)
                    ->orWhere('supervisor', '=', Admin::user()->id)
                    ->orWhere('tdv', '=', Admin::user()->id);
            });
        }
        // $roleName = Admin::user()->roles[0]->slug;
        $grid->model()
            ->where("contract_type", 0)
            ->where('branch_id', Admin::user()->branch_id)
            ->where(function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select('contract_id')
                        ->from('contract_acceptances')
                        ->whereColumn('contract_acceptances.contract_id', '=', 'contracts.id')
                        ->where('status', '!=', Constant::DONE_CONTRACT_STATUS);
                })
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotExists(function ($innerQuery) {
                            $innerQuery->select('contract_id')
                                ->from('contract_acceptances')
                                ->whereColumn('contract_acceptances.contract_id', '=', 'contracts.id')
                                ->where('status', '=', Constant::DONE_CONTRACT_STATUS);
                        });
                    });
            })
            ->orderBy('updated_at', 'desc');

        if (Utils::getCreateRole(Constant::CONTRACT_TABLE) != Admin::user()->roles[0]->slug) {
            $grid->disableCreateButton();
        }
        $grid->disableRowSelector();
        $grid->actions(function ($actions) use ($editStatus) {
            if (Admin::user()->isRole(Constant::DIRECTOR_ROLE) && in_array(Admin::user()->id, Constant::ROLE_RESET_CONTRACT)) {
                $actions->add(new ResetButton($actions->getKey()));
            }

            $doneStatus = Status::whereIn("id", $editStatus)->where("done", 1)->get();
            $doneStatusIds = $doneStatus->pluck('id')->toArray();
            $preAssessment = PreAssessment::where('contract_id', $actions->row->id)->first();

            if (
                !in_array($actions->row->status, $editStatus)
            ) {
                $actions->disableEdit();
            }
            if (
                !in_array($actions->row->status, $editStatus) ||
                in_array($actions->row->status, $doneStatusIds) ||
                (!($preAssessment && $preAssessment->status == 16))
            ) {
                $actions->disableDelete();
            }
        });
        $grid->column('comment', __('Bình luận'))->action(AddContractComment::class)->width(250)->filter('like');
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail ? $this->statusDetail->name : "";
        })->width(100);
        $grid->column('creator.name', __('Người tạo'));
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->date('created_date', 'Ngày hợp đồng');
            $filter->date('issue_date', 'Ngày cấp');
            $filter->date('from_date', 'Thời gian thực hiện từ ngày');
            $filter->date('to_date', 'Thời gian thực hiện đến ngày');
            $filter->equal('total_fee', 'Tổng phí dịch vụ')->integer();
            $filter->equal('advance_fee', 'Tạm ứng')->integer();
            $filter->where(function ($query) {
                $query->whereHas('tdvDetail', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Trưởng phòng nghiệp vụ');
            $filter->where(function ($query) {
                $query->whereHas('legalRepresentative', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Đại diện pháp luật');
            $filter->where(function ($query) {
                $query->whereHas('assistant', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Trợ lý thẩm định viên');
            $filter->where(function ($query) {
                $query->whereHas('supervisorDetail', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Kiểm soát viên');
            $filter->equal('net_revenue', 'Doanh thu thuần')->integer();
            $filter->where(function ($query) {
                $query->whereHas('statusDetail', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Trạng thái');
            $filter->where(function ($query) {
                $query->whereHas('creator', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Người tạo');
            $filter->between('created_at', 'Ngày tạo')->date();
            $filter->between('updated_at', 'Ngày cập nhật')->date();
        });

        $headings = [
            'Id',
            'Mã hợp đồng',
            'Mã yêu cầu SBKS',
            'Ngày hợp đồng',
            'Loại khách',
            'Mã số thuế',
            'Tên doanh nghiệp',
            'Địa chỉ',
            'Người đại diện',
            'Chức vụ',
            'Địa chỉ',
            'In hợp đồng',
            'Số CMND/CCCD',
            'Họ và tên',
            'Nơi cấp',
            'Ngày cấp',
            'Tài sản thẩm định giá',
            'Mục đích thẩm định giá',
            'Thời điểm thẩm định giá',
            'Thời gian thực hiện từ ngày',
            'Thời gian thực hiện đến ngày',
            'Tổng phí dịch vụ',
            'Loại biểu phí',
            'Tạm ứng',
            'Môi giới',
            'Nguồn',
            'Sale',
            'Trưởng phòng nghiệp vụ',
            'Đại diện pháp luật',
            'Thẩm định viên',
            'Trợ lý thẩm định viên',
            'Kiểm soát viên',
            'Doanh thu thuần',
            'Liên hệ',
            'Ghi chú',
            'Bình luận',
            'Trạng thái',
            'Người tạo',
            'Ngày tạo',
            'Ngày cập nhật'
        ];
        $grid->exporter(new ExcelExporter("reports.xlsx", [DataProcessors::class, 'processPreContractData'], Admin::user()->branch_id, $headings));
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
        $show = new Show(Contract::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('code', __('Mã yêu cầu SBKS'));
        $show->field('created_date', __('Ngày hợp đồng'))->as($dateFormatter);
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
        $show->field('issue_date', __('Ngày cấp'))->as($dateFormatter);
        $show->field('property', __('Tài sản thẩm định giá'))->unescape()->as(function ($property) {
            return "<textarea style='width: 100%; height: 200px;' readonly>$property</textarea>";
        });
        $show->field('purpose', __('Mục đích thẩm định giá'));
        $show->field('appraisal_date', __('Thời điểm thẩm định giá'));
        $show->field('from_date', __('Thời gian thực hiện từ ngày'))->as($dateFormatter);
        $show->field('to_date', __('Đến ngày'))->as($dateFormatter);
        $show->field('total_fee', __('Tổng phí dịch vụ'));
        $show->field('type_fees', __('Loại biểu phí'))->as(function ($typeFees) {
            if ($typeFees) {
                return $typeFees == 1 ? "Tiền mặt" : "Chuyển khoản";
            } else {
                return "";
            }
        });
        $show->field('advance_fee', __('Tạm ứng'));
        $show->field('broker', __('Môi giới'));
        $show->field('source', __('Nguồn'));
        $show->field('sale', __('Sale'));

        $show->field('tdv', __('Trưởng phòng nghiệp vụ'))->as($convertIdToNameUser);
        $show->field('legal_representative', __('Đại diện pháp luật'))->as($convertIdToNameUser);
        $show->field('tdv_migrate', __('Thẩm định viên'))->as($convertIdToNameUser);
        $show->field('assistant.name', __('Trợ lý thẩm định viên'));
        $show->field('supervisor', __('Kiểm soát viên'))->as($convertIdToNameUser);
        $show->field('net_revenue', __('Doanh thu thuần'));
        $show->field('contact', __('Liên hệ'));
        $show->field('note', __('Ghi chú'));
        $show->document(__('File đính kèm'))->unescape()->as(function ($value) {
            $urlsHtml = "";
            foreach ($value as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $show->field('creator.name', __('Người tạo'));
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
        $checkStatus = function ($form) {
            if (!$id = $form->model()->status) {
                return 'required:status';
            }
        };
        $currentStatus = 0;
        $form = new Form(new Contract());
        $form->divider('1. Thông tin hợp đồng');
        if ($form->isEditing()) {
            $id = request()->route()->parameter('pre_contract');
            if (is_null($id)) {
                $id = request()->route()->parameter('assigned_contract');
            }
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => $model->contract_type == Constant::OFFICIAL_CONTRACT_TYPE ? Constant::CONTRACT_TABLE : Constant::PRE_CONTRACT_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                if (!is_null($nextStatus->nextStatus)) {
                    $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
                }
            }
            $form->text('code', "Mã hợp đồng")->readonly()->setWidth(2, 2);
            if ($model->contract_type == Constant::PRE_CONTRACT_TYPE && Status::find($model->status)->done == 1) {
                $form->select('contract_type', __('Loại hợp đồng'))->options(Constant::CONTRACT_TYPE)->setWidth(5, 2)->when(Constant::PRE_CONTRACT_TYPE, function (Form $form) use ($status, $checkStatus) {
                    $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->rules($checkStatus);
                })->when(Constant::OFFICIAL_CONTRACT_TYPE, function (Form $form) use ($checkStatus) {
                    $nextStatuses = StatusTransition::where("table", Constant::CONTRACT_TABLE)->whereNull("status_id")->get();
                    foreach ($nextStatuses as $nextStatus) {
                        $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
                    }
                    if (in_array("Lưu nháp", $status)) {
                        $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp", $status))->setWidth(5, 2)->rules($checkStatus);
                    } else {
                        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->rules($checkStatus);
                    }
                });
            } else {
                $form->select('contract_type', __('Loại hợp đồng'))->options(Constant::CONTRACT_TYPE)->setWidth(5, 2)->readOnly();
                $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->rules($checkStatus);
            }
        } else {
            $form->select('contract_type', __('Loại hợp đồng'))->options([0 => "Sơ bộ"])->default(0)->readonly();
            $form->text('code', "Mã hợp đồng")->default(Utils::generateCode("contracts", Admin::user()->branch_id, 0))->readonly()->setWidth(2, 2);
            $form->date('created_date', __('Ngày hợp đồng'))->format('DD-MM-YYYY')->required();
            $form->hidden('created_by')->default(Admin::user()->id);
            $nextStatuses = StatusTransition::where("table", Constant::PRE_CONTRACT_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            if (in_array("Lưu nháp (Sơ bộ)", $status)) {
                $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp (Sơ bộ)", $status))->setWidth(5, 2)->rules($checkStatus);
            } else {
                $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->rules($checkStatus);
            }
        }
        $form->divider('2. Thông tin khách hàng');
        $form->select('customer_type', __('Loại khách hàng'))->options(Constant::CUSTOMER_TYPE)->setWidth(2, 2)->required()->default(1)->when(1, function (Form $form) {
            $form->select('selected_id_number', __('Chọn CMND/CCCD'))->options(
                Contract::select(DB::raw('CONCAT(personal_name," ", id_number, " mã hợp đồng ", IFNULL(code,"")) AS code, id'))->where('branch_id', '=', Admin::user()->branch_id)->pluck('code', 'id')
            );
            $form->text('id_number', __('Số CMND/CCCD'));
            $form->text('personal_name', __('Họ và tên bên thuê dịch vụ'));
            $form->text('personal_address', __('Địa chỉ'));
            $form->date('issue_date', __('Ngày cấp'))->format('DD-MM-YYYY');
            $form->text('issue_place', __('Nơi cấp'));
        })->when(2, function (Form $form) {
            $form->select('selected_tax_number', __('Chọn mã số thuê'))->options(
                Contract::select(DB::raw('CONCAT(business_name, " ", tax_number, " mã hợp đồng ", IFNULL(code,"")) AS code, id'))->where('branch_id', '=', Admin::user()->branch_id)->pluck('code', 'id')
            );
            $form->text('tax_number', __('Mã số thuế'));
            $form->text('business_name', __('Tên doanh nghiệp'));
            $form->text('business_address', __('Địa chỉ doanh nghiệp'));
            $form->text('representative', __('Người đại diện'));
            $form->text('position', __('Chức vụ'));
        })->required();

        $docsConfigsRepresentative = DocsConfig::where(function ($query) {
            $query->where("type", "Hợp đồng cá nhân")
                ->orWhere("type", "Hợp đồng doanh nghiệp");
        })
            ->where("branch_id", Admin::user()->branch_id)
            ->where("key", "dai_dien")
            ->pluck("value", "value");
        $docsConfigsAuthorization = DocsConfig::where(function ($query) {
            $query->where("type", "Hợp đồng cá nhân")
                ->orWhere("type", "Hợp đồng doanh nghiệp");
        })
            ->where("branch_id", Admin::user()->branch_id)
            ->where("key", "uy_quyen")
            ->pluck("description", "description");
        $docsConfigsPosition = DocsConfig::where(function ($query) {
            $query->where("type", "Hợp đồng cá nhân")
                ->orWhere("type", "Hợp đồng doanh nghiệp");
        })
            ->where("branch_id", Admin::user()->branch_id)
            ->where("key", "chuc_vu")
            ->pluck("value", "value");
        $docsConfigsStk = DocsConfig::where(function ($query) {
            $query->where("type", "Hợp đồng cá nhân")
                ->orWhere("type", "Hợp đồng doanh nghiệp");
        })
            ->where("branch_id", Admin::user()->branch_id)
            ->where("key", "stk")
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item['value'] => $item['value'] . ' - ' . $item['description']];
            });
        $form->divider('2.1. In chứng từ');
        $form->select('docs_representative', __('Đại diện'))->options($docsConfigsRepresentative);
        $form->select('docs_authorization', __('Uỷ quyền'))->options($docsConfigsAuthorization);
        $form->select('docs_position', __('Chức vụ'))->options($docsConfigsPosition);
        $form->select('docs_stk', __('Số tài khoản'))->options($docsConfigsStk);

        $form->divider('3. Thông tin về hồ sơ thẩm định giá');
        $form->textarea('property', __('Tài sản thẩm định giá'))->rows(5)->required();
        $form->text('purpose', __('Mục đích thẩm định giá'))->required();
        $form->text('appraisal_date', __('Thời điểm thẩm định giá'))->required();
        $form->date('from_date', __('Thời gian thực hiện từ ngày'))->format('DD-MM-YYYY')->required();
        $form->date('to_date', __('Đến ngày'))->format('DD-MM-YYYY')->required();

        $form->divider('4. Thông tin phí và thanh toán');
        $form->currency('total_fee', __('Tổng phí dịch vụ'))->symbol('VND');
        $form->select('type_fees', __('Loại biểu phí'))->options(Constant::TYPE_FEE_CONTRACT)->default(2);
        $form->currency('advance_fee', __('Tạm ứng'))->symbol('VND');

        $form->divider('5. Thông tin phiếu giao việc');
        $form->text('broker', __('Môi giới'))->required();
        $form->text('source', __('Nguồn'))->required();
        $form->text('sale', __('Sale'))->required();
        $form->currency('net_revenue', __('Doanh thu thuần'))->symbol('VND');

        if (Constant::PRE_CONTRACT_REQUIRE == $currentStatus || Constant::OFFICIAL_CONTRACT_REQUIRE == $currentStatus) {
            $form->select('tdv', __('Trưởng phòng nghiệp vụ'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'))->required();
        } else {
            $form->select('tdv', __('Trưởng phòng nghiệp vụ'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        }

        $form->select('legal_representative', __('Đại diện pháp luật'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));

        $form->select('tdv_migrate', __('Thẩm định viên'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->select('tdv_assistant', __('Trợ lý thẩm định viên'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->whereHas('roles', function ($q) {
            $q->where('id', Constant::BUSINESS_STAFF);
        })->pluck('name', 'id'));
        $form->select('supervisor', __('Kiểm soát viên'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->divider('6. Thông tin khác');
        $form->text('contact', __('Liên hệ'));
        $form->textarea('note', __('Ghi chú'))->rows(5);
        $form->multipleFile('document', __('Tài liệu'))->removable();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });

        // callback before save
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->code = Utils::generateCode("contracts", Admin::user()->branch_id, 0);
            }
            $dateFields = ['created_date', 'issue_date', 'from_date', 'to_date'];
            foreach ($dateFields as $field) {
                $value = $form->input($field);
                if (!empty($value)) {
                    try {
                        $formattedDate = Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
                        $form->input($field, $formattedDate);
                    } catch (\Exception $e) {
                    }
                }
            }
        });

        $contracts = json_encode(Contract::where('branch_id', '=', Admin::user()->branch_id)->get()->keyBy("id"));
        $script = <<<EOT
        var contracts = $contracts;
        var customerType;

        $(document).on('change', ".selected_id_number, .selected_tax_number", function () {
            var contract = contracts[this.value];
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
        });
        $(document).ready(function () {
            customerType = $('select[name="customer_type"]').val();
            $('select[name="customer_type"]').on('change', function () {
                customerType = $(this).val();
            });
        });
        EOT;

        Admin::script($script);

        return $form;
    }
}