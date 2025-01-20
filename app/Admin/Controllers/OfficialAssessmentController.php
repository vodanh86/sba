<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Export\DataProcessors;
use App\Http\Models\OfficialAssessment;
use App\Admin\Extensions\ExcelExporter;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Contract;
use App\Http\Models\AdminUser;
use App\Admin\Actions\Document\AddOfficialAssessmentComment;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

class OfficialAssessmentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Kết quả thẩm định chính thức';

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
        $nextStatuses = Utils::getNextStatuses(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "approvers");
        $grid = new Grid(new OfficialAssessment());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('official_assessment.contract_id'));
        $grid->column('created_date', __('Ngày hợp đồng'))->display(function ($createdDate) {
            if($createdDate == null){
                return "";
            }else{
                $convertDay = Carbon::parse($createdDate)->timezone(Config::get('app.timezone'));
                return $convertDay->format('d/m/Y');
            }
        });
        $grid->column('certificate_code', __('Mã chứng thư'))->filter('like');
        $grid->column('certificate_date', __('Ngày chứng thư'))->display($dateFormatter);
        $grid->column('contract.property', __('Tài sản thẩm định giá'))->width(150);
        $grid->column('finished_date', __('Ngày hoàn thành'))->display($dateFormatter)->width(150);
        $grid->column('performerDetail.name', __('Người thực hiện'));
        $grid->column('assessment_type', __('Phưong pháp thẩm định'))->display(function ($types) {
            if (!is_null($types)) {
                return join(", ", $types);
            }
        })->filter('like');
        $grid->column('note', __('Ghi chú'))->filter('like');
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->column('official_value', __('Giá trị chính thức'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('comment', __('Bình luận'))->action(AddOfficialAssessmentComment::class)->width(150)->filter('like');
        $grid->column('document', __('Document'))->display(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $grid->column('print', __('In chứng thư'))->display(function () {
            return "<a class=\"fa fa-print\" href='print-official-assessment?id=" . $this->id . "' target='_blank'></a>";
        });
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);

        // $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id);
        $grid->model()->orderByDesc('id');
        if (Utils::getCreateRole(Constant::OFFICIAL_ASSESS_TABLE) != Admin::user()->roles[0]->slug) {
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
            $filter->date('created_date', 'Ngày hợp đồng');
            $filter->date('certificate_date', 'Ngày chứng thư');
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
            $filter->equal('official_value', 'Giá trị chính thức')->integer();
            $filter->between('created_at', 'Ngày tạo')->date();
            $filter->between('updated_at', 'Ngày cập nhật')->date();
        });

        $headings = [
            'Id',
            'Mã hợp đồng',
            'Ngày hợp đồng',
            'Mã chứng thư',
            'Ngày chứng thư',
            'Tài sản thẩm định giá',
            'Ngày hoàn thành',
            'Người thực hiện',
            'Phương pháp thẩm định',
            'Ghi chú',
            'Trạng thái',
            'Giá trị chính thức',
            'Bình luận',
            'Ngày tạo',
            'Ngày cập nhật'
        ];
        if (Utils::isSuperManager(Admin::user()->roles[0]->id)) {
            $grid->exporter(new ExcelExporter("reports.xlsx", [DataProcessors::class, 'processOfficialAssessmentData'], Admin::user()->branch_id, $headings));
        } else {
            $grid->disableExport();
        }
        return $grid;
    }
    protected function processData()
    {
        $processedData = array();
        foreach (OfficialAssessment::all() as $index => $officialAssessment) {
            $performerDetail = optional(AdminUser::find($officialAssessment->performer))->name;
            $processedData[] = [
                $officialAssessment->id, $officialAssessment->contract->code, $officialAssessment->certificate_code, $officialAssessment->certificate_date, $officialAssessment->contract->property,
                $officialAssessment->finished_date, $performerDetail, $officialAssessment->assessment_type, $officialAssessment->note, $officialAssessment->statusDetail->name, $officialAssessment->official_value,
                $officialAssessment->comment, $officialAssessment->created_at, $officialAssessment->updated_at
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
        $show = new Show(OfficialAssessment::findOrFail($id));
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt)->timezone(Config::get('app.timezone'));
            return $carbonUpdatedAt->format('d/m/Y');
        };

        $show->field('id', __('Id'));
        $show->field('contract.code', __('official_assessment.contract_id'));
        $show->field('created_date', __('Ngày hợp đồng'))->as($dateFormatter);
        $show->field('certificate_code', __('Mã chứng thư'));
        $show->field('certificate_date', __('Ngày chứng thư'))->as($dateFormatter);
        $show->field('contract.property', __('Tài sản thẩm định giá'))->unescape()->as(function ($property) {
            return "<textarea style='width: 100%; height: 200px;' readonly>$property</textarea>";
        });
        $show->field('finished_date', __('Ngày hoàn thành'))->as($dateFormatter);
        $show->field('performerDetail.name', __('Người thực hiện'));
        $show->field('assessment_type', __('Phương pháp thẩm định'))->as(function ($types) {
            if (!is_null($types)) {
                return join(", ", $types);
            }
        });
        $show->field('note', __('Ghi chú'));
        $show->field('statusDetail.name', __('Trạng thái'));

        $show->field('official_value', __('Giá trị chính thức'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        });
        $show->field('comment', __('Bình luận'));
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
        $form = new Form(new OfficialAssessment());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('official_assessment');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::OFFICIAL_ASSESS_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
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
            $nextStatuses = StatusTransition::where("table", Constant::OFFICIAL_ASSESS_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
            $form->select('contract_id', __('valuation_document.contract_id'))
                ->options(
                    Contract::where("branch_id", Admin::user()->branch_id)
                        ->where('contract_type', '=', Constant::OFFICIAL_CONTRACT_TYPE)
                        ->where('status', Constant::CONTRACT_INPUTTING_STATUS)
                        ->where('tdv_assistant', '=', Admin::user()->id)
                        ->whereNotIn('id', OfficialAssessment::pluck('contract_id')->all())
                        ->pluck('code', 'id')
                )
                ->required()
                ->creationRules(['required', "unique:official_assessments"])
                ->updateRules(['required', "unique:official_assessments,contract_id,{{id}}"]);
        }
        $form->textarea('property', __('Tài sản thẩm định giá'))->disable();
        // $form->text('certificate_code', __('Mã chứng thư'));
        $form->hidden('certificate_code')->default('');
        $form->date('created_date', __('Ngày hợp đồng'))->format('DD-MM-YYYY');
        $form->date('certificate_date', __('Ngày chứng thư'))->format('DD-MM-YYYY');
        $form->date('finished_date', __('Ngày hoàn thành'))->format('DD-MM-YYYY');

        $form->select('performer', __('Người thực hiện'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->multipleSelect('assessment_type', __('Phương pháp thẩm định'))->options(Constant::ASSESSMENT_TYPE)->setWidth(5, 2)->required();
        $form->textarea('note', __('Ghi chú'))->rows(5);
        $form->currency('official_value', __('Giá trị chính thức'))->symbol('VND');
        $form->text('comment', __('Bình luận'));
        $form->multipleFile('document', __('Tài liệu'))->removable();

        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        if (in_array("Lưu nháp", $status)) {
            $form->select('status', __('Trạng thái'))->options($status)->default(array_search("Lưu nháp", $status))->setWidth(5, 2)->required();
        } else {
            $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        }
        $form->saving(function (Form $form) {
            $dateFields = ['created_date', 'finished_date', 'certificate_date'];
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
        // callback after save
        $form->saved(function (Form $form) {
            $contract = Contract::find($form->model()->contract_id);
            $contract->created_date = $form->model()->created_date;
            $contract->save();
        });
        // $url = 'http://127.0.0.1:8000/api/contract';
        $url = env('APP_URL') . '/api/contract';

        $script = <<<EOT
        $(function() {
            var contractId = $(".contract_id").val();
            $.get("$url",{q : contractId}, function (data) {
                $(".property").val(data.property);
                $("#certificate_code").val(data.code);
                $("#created_date").val(data.created_date);
            });
            $(document).on('change', ".contract_id", function () {
                $.get("$url",{q : this.value}, function (data) {
                $(".property").val(data.property);
                $("#certificate_code").val(data.code);
                $("#created_date").val(data.created_date);
                });
            });
        });
        EOT;

        Admin::script($script);
        return $form;
    }
}
