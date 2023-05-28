<?php

namespace App\Admin\Controllers;

use App\Http\Models\OfficialAssessment;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Contract;
use App\Http\Models\AdminUser;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

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
        $grid->column('contract.name', __('Contract id'));
        $grid->column('document', __('Document'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</span>";
        });
        $grid->column('finished_date', __('Finished date'));
        $grid->column('performerDetail.name', __('Performer'));
        $grid->column('note', __('Note'));
        $grid->column('status')->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });

        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        if (Utils::getCreateRole(Constant::OFFICIAL_ASSESS_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
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
        $show->field('contract_id', __('Contract id'));
        $show->document()->file();
        $show->field('finished_date', __('Finished date'));
        $show->field('performerDetail.name', __('Performer'));
        $show->field('note', __('Note'));
        $show->field('statusDetail.name', __('Status'));

        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

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
            $nextStatuses = StatusTransition::where("table", Constant::OFFICIAL_ASSESS_TABLE)->whereNull("status_id")->first();
            $status[$nextStatuses->next_status_id] = $nextStatuses->nextStatus->name;
        }
        $form->select('contract_id')->options(Contract::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->file('document', __('Document'));
        $form->date('finished_date', __('Finished date'))->default(date('Y-m-d'));
        $form->select('performer', __('Performer'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->text('note', __('Note'));
        $form->select('status', __('Status'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}
