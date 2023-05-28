<?php

namespace App\Admin\Controllers;

use App\Http\Models\TaskNote;
use App\Http\Models\Contract;
use App\Http\Models\AdminUser;
use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TaskNoteController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Phiếu giao việc';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $nextStatuses = Utils::getNextStatuses(Constant::TASK_NOTE_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::TASK_NOTE_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::TASK_NOTE_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::TASK_NOTE_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new TaskNote());
        $grid->column('id', __('Id'));
        $grid->column('contract.name', __('Contract id'));
        $grid->column('source', __('Source'));
        $grid->column('sale.name', __('Sale id'));
        $grid->column('tdvDetail.name', __('Tdv'));
        $grid->column('tdvAssistantDetail.name', __('Tdv assistant'));
        $grid->column('controllerDetail.name', __('Controller'));
        $grid->column('estimated_date', __('Estimated date'));
        $grid->column('status')->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        if (Utils::getCreateRole(Constant::TASK_NOTE_TABLE) != Admin::user()->roles[0]->slug){
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
        $show = new Show(TaskNote::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('contract.name', __('Contract id'));
        $show->field('source', __('Source'));
        $show->field('sale.name', __('Sale id'));
        $show->field('tdvDetail.name', __('Tdv'));
        $show->field('tdvAssistantDetail.name', __('Tdv assistant'));
        $show->field('controllerDetail.name', __('Controller'));
        $show->field('estimated_date', __('Estimated date'));
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
        $form = new Form(new TaskNote());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('task_note');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::TASK_NOTE_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach($nextStatuses as $nextStatus){
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::TASK_NOTE_TABLE)->whereNull("status_id")->first();
            $status[$nextStatuses->next_status_id] = $nextStatuses->nextStatus->name;
        }
        $form->select('contract_id')->options(Contract::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->text('source', __('Source'));
        $form->select('sale_id')->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->select('tdv', __('Tdv'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->select('tdv_assistant', __('Tdv assistant'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->select('controller', __('Controller'))->options(AdminUser::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->date('estimated_date', __('Estimated date'))->default(date('Y-m-d'));
        $form->select('status', __('Status'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}
