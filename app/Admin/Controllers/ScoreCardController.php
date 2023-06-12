<?php

namespace App\Admin\Controllers;

use App\Http\Models\ScoreCard;
use App\Http\Models\Contract;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

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
        $nextStatuses = Utils::getNextStatuses(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new ScoreCard());
        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('score', __('Nguồn'));
        $grid->column('document', __('Tài liệu'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</span>";
        });
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
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
        if (Utils::getCreateRole(Constant::SCORE_CARD_TABLE) != Admin::user()->roles[0]->slug){
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
        $show = new Show(ScoreCard::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        $show->field('branch_id', __('Id Chi nhánh'));
        $show->field('contract_id', __('Mã hợp đồng'));
        $show->document()->file();
        $show->field('status', __('Trạng thái'));
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
        $form = new Form(new ScoreCard());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('score_card');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::SCORE_CARD_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach($nextStatuses as $nextStatus){
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::SCORE_CARD_TABLE)->whereNull("status_id")->first();
            $status[$nextStatuses->next_status_id] = $nextStatuses->nextStatus->name;
        }
        $form->select('contract_id')->options(Contract::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->number('score', __('Nguồn'));
        $form->file('document', __('Tài liệu'));
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}
