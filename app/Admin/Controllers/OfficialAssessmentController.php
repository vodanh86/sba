<?php

namespace App\Admin\Controllers;

use App\Http\Models\OfficialAssessment;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Contract;
use App\Http\Models\AdminUser;
use App\Admin\Actions\Document\AddOfficialAssessmentComment;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

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
        $nextStatuses = Utils::getNextStatuses(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::OFFICIAL_ASSESS_TABLE, Admin::user()->roles[0]->slug, "approvers");
        $grid = new Grid(new OfficialAssessment());

        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('official_assessment.contract_id'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'))->width(150);
        $grid->column('finished_date', __('Ngày hoàn thành'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y');
        })->width(150);
        $grid->column('performerDetail.name', __('Người thực hiện'));
        $grid->column('assessment_type', __('Phưong pháp thẩm định'))->display(function ($types) {
            if (!is_null($types)){
                return join(", ", $types);
            }
        });
        $grid->column('note', __('Chú ý'));
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->column('official_value', __('Giá trị chính thức'))->display(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        })->width(150);
        $grid->column('comment', __('Ghi chú'))->action(AddOfficialAssessmentComment::class)->width(150);
        $grid->column('document', __('Document'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</a>";
        });
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::OFFICIAL_ASSESS_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });
        $grid->filter(function($filter){
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
        $show = new Show(OfficialAssessment::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('contract.code', __('official_assessment.contract_id'));
        $show->field('contract.property', __('Tài sản thẩm định giá'));

        $show->field('finished_date', __('Ngày hoàn thành'));
        $show->field('performerDetail.name', __('Người thực hiện'));
        $show->field('assessment_type', __('Phương pháp thẩm định'));
        $show->field('note', __('Chú ý'));
        $show->field('statusDetail.name', __('Trạng thái'));

        $show->field('official_value', __('Giá trị chính thức'))->as(function ($money) {
            return number_format($money, 2, ',', ' ') . " VND";
        });
        $show->field('comment', __('Ghi chú'));
        $show->field('document', __('Tài liệu'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</a>";
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
            $nextStatuses = StatusTransition::where(["table" => Constant::OFFICIAL_ASSESS_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach($nextStatuses as $nextStatus){
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::OFFICIAL_ASSESS_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        }
        $form->select('contract_id', __('official_assessment.contract_id'))->options(Contract::where("branch_id", Admin::user()->branch_id)
        ->where('contract_type', Constant::OFFICIAL_CONTRACT_TYPE)->where('status', Constant::CONTRACT_INPUTTING_STATUS)->where('tdv_assistant', '=', Admin::user()->id)->pluck('code', 'id'));
        $form->text('property', __('Tài sản thẩm định giá'))->disable();
        $form->date('finished_date', __('Ngày hoàn thành'))->default(date('Y-m-d'));

        $form->select('performer', __('Người thực hiện'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->multipleSelect('assessment_type', __('Phương pháp thẩm định'))->options(Constant::ASSESSMENT_TYPE)->setWidth(5, 2)->required();
        $form->text('note', __('Chú ý'));
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        
        $form->number('official_value', __('Giá trị chính thức'));
        $form->text('comment', __('Ghi chú'));
        $form->file('document', __('Tài liệu'));

        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        
        // $url = 'http://127.0.0.1:8000/api/contract';
        $url = env('APP_URL') . '/api/contract';
        
        $script = <<<EOT
        $(document).on('change', ".form-control", function () {
            $.get("$url",{q : this.value}, function (data) {
            $("#property").val(data.property);
        });
        });
        EOT;

        Admin::script($script);
        return $form;
    }
}
