<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Export\DataProcessors;
use App\Http\Models\PreAssessment;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Contract;
use App\Http\Models\AdminUser;
use App\Admin\Extensions\ExcelExporter;
use App\Admin\Actions\Document\AddPreAssessmentComment;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

class PreAssessmentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Kết quả thẩm định sơ bộ';

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

        $nextStatuses = Utils::getNextStatuses(Constant::PRE_ASSESS_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::PRE_ASSESS_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::PRE_ASSESS_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::PRE_ASSESS_TABLE, Admin::user()->roles[0]->slug, "approvers");
        $grid = new Grid(new PreAssessment());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'))->width(150);
        $grid->column('finished_date', __('Ngày hoàn thành'))->display($dateFormatter)->width(150);
        $grid->column('performerDetail.name', __('Người thực hiện'))->width(150);
        $grid->column('note', __('Chú ý'))->filter('like');
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        })->width(200);
        $grid->column('pre_value', __('Giá trị sơ bộ'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('comment', __('Ghi chú'))->action(AddPreAssessmentComment::class)->width(150)->filter('like');
        $grid->column('document', __('Tài liệu'))->display(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::PRE_ASSESS_TABLE) != Admin::user()->roles[0]->slug) {
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus) {
            $doneStatus = Status::whereIn("id", $editStatus)->where("done", 1)->get();
            $doneStatusIds = $doneStatus->pluck('id')->toArray();
            if (!in_array($actions->row->status, $editStatus) || in_array($actions->row->status, $doneStatusIds)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
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
            $filter->date('finished_date', 'Ngày hoàn thành');
            $filter->where(function ($query) {
                $query->whereHas('performerDetail', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Người thực hiện');
            $filter->where(function ($query) {
                $query->whereHas('statusDetail', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Trạng thái');
            $filter->equal('pre_value', 'Giá trị sơ bộ')->integer();
            $filter->between('created_at', 'Ngày tạo')->date();
            $filter->between('updated_at', 'Ngày cập nhật')->date();
        });

        $headings = [
            'Id',
            'Mã hợp đồng',
            'Tài sản thẩm định giá',
            'Ngày hoàn thành',
            'Người thực hiện',
            'Chú ý',
            'Ghi chú',
            'Trạng thái',
            'Giá trị sơ bộ',
            'Ngày tạo',
            'Ngày cập nhật'
        ];
        if (Utils::isSuperManager(Admin::user()->roles[0]->id)) {
            $grid->exporter(new ExcelExporter("reports.xlsx", [DataProcessors::class, 'processPreAssessmentData'], Admin::user()->branch_id, $headings));
        } else {
            $grid->disableExport();
        }
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
        $show = new Show(PreAssessment::findOrFail($id));
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };

        $show->field('id', __('Id'));
        $show->field('contract.code', __('Mã hợp đồng'));
        $show->field('contract.property', __('Tài sản thẩm định giá'))->unescape()->as(function ($property) {
            return "<textarea style='width: 100%; height: 200px;' readonly>$property</textarea>";
        });
        $show->field('finished_date', __('Ngày hoàn thành'))->as($dateFormatter);
        $show->field('performerDetail.name', __('Người thực hiện'));
        $show->field('note', __('Chú ý'));
        $show->field('statusDetail.name', __('Trạng thái'));

        $show->field('pre_value', __('Giá trị sơ bộ'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        });
        $show->field('comment', __('Ghi chú'));
        $show->field('document', __('Tài liệu'))->unescape()->as(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
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
        $form = new Form(new PreAssessment());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('pre_assessment');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::PRE_ASSESS_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $pluckDefaultContractId = [$model->contract_id => Contract::where('id', $model->contract_id)->first()->code];
            $pluckDefaultContractId = [$model->contract_id => Contract::where('id', $model->contract_id)->first()->code];
            $form->select('contract_id', __('valuation_document.contract_id'))
                ->default(0)
                ->options($pluckDefaultContractId)
                ->required();
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::PRE_ASSESS_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->select('contract_id', __('valuation_document.contract_id'))
                ->options(
                    Contract::where("branch_id", Admin::user()->branch_id)
                        ->where('status', Constant::PRE_CONTRACT_INPUTTING_STATUS)
                        ->where('contract_type', Constant::PRE_CONTRACT_TYPE)
                        ->where('tdv_assistant', Admin::user()->id)
                        ->whereNotIn('id', PreAssessment::pluck('contract_id')->all())
                        ->pluck('code', 'id')
                )
                ->required()
                ->creationRules(['required', "unique:pre_assessments"])
                ->updateRules(['required', "unique:pre_assessments,contract_id,{{id}}"]);
        }
        $form->textarea('property', __('Tài sản thẩm định giá'))->disable();
        $form->date('finished_date', __('Ngày hoàn thành'))->format('DD-MM-YYYY');
        $form->select('performer', __('Người thực hiện'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->text('note', __('Chú ý'));
        $form->currency('pre_value', __('Giá trị sơ bộ'))->symbol('VND');
        $form->textarea('note', __('Ghi chú'))->rows(5);
        $form->multipleFile('document', __('Tài liệu'))->removable();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        if (in_array("Lưu nháp", $status)) {
            $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp", $status))->setWidth(5, 2)->required();
        } else {
            $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        }
        // $url = 'https://valuation.sba.net.vn/api/contract';
        $url = env('APP_URL') . '/api/contract';
        $form->saving(function (Form $form) {
            $dateFields = ['finished_date'];
            foreach ($dateFields as $field) {
                $value = $form->input($field);
                if (!empty($value)) {
                    try {
                        $formattedDate = \Carbon\Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
                        $form->input($field, $formattedDate);
                    } catch (\Exception $e) {
                    }
                }
            }
        });
        $script = <<<EOT
        $(function() {
            var contractId = $(".contract_id").val();
            $.get("$url",{q : contractId}, function (data) {
                $(".property").val(data.property);
            });
            $(document).on('change', ".contract_id", function () {
                $.get("$url",{q : this.value}, function (data) {
                $(".property").val(data.property);
                });
            });
        });
        EOT;

        Admin::script($script);

        return $form;
    }
}
