<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Document\AddScoreCardComment;
use App\Admin\Extensions\Export\DataProcessors;
use App\Http\Models\ScoreCard;
use App\Http\Models\Contract;
use App\Admin\Extensions\ExcelExporter;
use App\Http\Models\OfficialAssessment;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use App\Http\Models\AdminUser;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;
use Config;

class ScoreCardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Phiếu chấm điểm';

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
        $nextStatuses = Utils::getNextStatuses(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new ScoreCard());
        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'))->width(150);
        $grid->column('score', __('Điểm'))->filter('like');
        $grid->column('basic_error', __('Lỗi cơ bản'))->filter('like');
        $grid->column('business_error', __('Lỗi nghiệp vụ'))->filter('like');
        $grid->column('serious_error', __('Lỗi nghiêm trọng'))->filter('like');
        $grid->column('document', __('Tệp đính kèm'))->display(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $grid->column('note', __('Ghi chú'))->filter('like');
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });

        $grid->column('comment', __('Bình luận'))->action(AddScoreCardComment::class)->width(100)->filter('like');
        $grid->column('creator.name', __('Người tạo'));
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::SCORE_CARD_TABLE) != Admin::user()->roles[0]->slug) {
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
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
            'Tài sản thẩm định giá',
            'Điểm',
            'Lỗi cơ bản',
            'Lỗi nghiệp vụ',
            'Lỗi nghiêm trọng',
            'Ghi chú',
            'Trạng thái',
            'Bình luận',
            'Ngày tạo',
            'Ngày cập nhật'
        ];
        if (Utils::isSuperManager(Admin::user()->roles[0]->id)) {
            $grid->exporter(new ExcelExporter("reports.xlsx", [DataProcessors::class, 'processScoreCardData'], Admin::user()->branch_id, $headings));
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
        $show = new Show(ScoreCard::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('branch_id', __('Id Chi nhánh'));
        $show->field('contract_id', __('Mã hợp đồng'));
        $show->field('contract.property', __('Tài sản thẩm định giá'))->unescape()->as(function ($property) {
            return "<textarea style='width: 100%; height: 200px;' readonly>$property</textarea>";
        });
        $show->field('score', __('Điểm'));
        $show->field('basic_error', __('Lỗi cơ bản'));
        $show->field('business_error', __('Lỗi nghiệp vụ'));
        $show->field('serious_error', __('Lỗi nghiêm trọng'));
        $show->field('document', __('Tệp đính kèm'))->unescape()->as(function ($urls) {
            $urlsHtml = "";
            foreach ($urls as $i => $url) {
                $urlsHtml .= "<a href='" . env('APP_URL') . '/storage/' . $url . "' target='_blank'>" . basename($url) . "</a><br/>";
            }
            return $urlsHtml;
        });
        $show->field('note', __('Ghi chú'));
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
        $avaiContracts = array();
        $contracts = Contract::where("branch_id", Admin::user()->branch_id)->where('contract_type', Constant::OFFICIAL_CONTRACT_TYPE)
            ->where("supervisor", '=', Admin::user()->id)->whereNotIn('id', ScoreCard::pluck('contract_id')->all())->get();

        foreach ($contracts as $i => $contract) {
            $officialAssessments = OfficialAssessment::where('contract_id', '=', $contract->id)->where('status', '=', Constant::ASSESSMENT_DONE_STATUS)->get();
            if (count($officialAssessments) > 0) {
                $avaiContracts[$contract->id] = $contract->code;
            }
        }
        $form = new Form(new ScoreCard());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('score_card');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::SCORE_CARD_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%' . Admin::user()->roles[0]->slug . '%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::SCORE_CARD_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        }
        $form->select('contract_id', __('valuation_document.contract_id'))->options($avaiContracts)->required()
            ->creationRules(['required', "unique:score_cards"])
            ->updateRules(['required', "unique:score_cards,contract_id,{{id}}"]);
        $form->textarea('property', __('Tài sản thẩm định giá'))->disable();
        $form->number('score', __('Điểm'));
        $form->number('basic_error', __('Lỗi cơ bản'));
        $form->number('business_error', __('Lỗi nghiệp vụ'));
        $form->number('serious_error', __('Lỗi nghiêm trọng'));
        $form->multipleFile('document', __('Tài liệu'))->removable();
        $form->select('creator_id', __('Người tạo'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'))->default(Admin::user()->id);
        $form->textarea('note', __('Ghi chú'))->rows(5);
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->hidden('created_by')->default(Admin::user()->id);

        // $url = 'http://127.0.0.1:8000/api/contract';
        $url = env('APP_URL') . '/api/contract';

        $script = <<<EOT
        $(document).on('change', ".contract_id", function () {
            $.get("$url",{q : this.value}, function (data) {
            $(".property").val(data.property)
        });
        });
        EOT;

        Admin::script($script);

        return $form;
    }
}
