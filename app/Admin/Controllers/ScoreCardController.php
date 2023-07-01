<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Document\AddScoreCardComment;
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
        $dateFormatter = function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        };
        $nextStatuses = Utils::getNextStatuses(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new ScoreCard());
        $grid->column('id', __('Id'));
        $grid->column('contract.code', __('Mã hợp đồng'));
        $grid->column('contract.property', __('Tài sản thẩm định giá'))->width(150);
        $grid->column('score', __('Điểm'));
        $grid->column('error_score', __('Lỗi điểm'))->width(150);
        $grid->column('document', __('Tệp đính kèm'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/public/storage/'.$url."' target='_blank'>".basename($url)."</a>";
        });
        $grid->column('note', __('Ghi chú'));
        $grid->column('status', __('Trạng thái'))->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
        });

        $grid->column('comment', __('Bình luận'))->action(AddScoreCardComment::class)->width(100);
        $grid->column('created_at', __('Ngày tạo'))->display($dateFormatter)->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display($dateFormatter)->width(150);

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
        $show = new Show(ScoreCard::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('branch_id', __('Id Chi nhánh'));
        $show->field('contract_id', __('Mã hợp đồng'));
        $show->field('contract.property', __('Tài sản thẩm định giá'));
        $show->field('score', __('Điểm'));
        $show->field('error_score', __('Lỗi điểm'));
        $show->field('document', __('Tệp đính kèm'))->unescape()->as(function ($url) {
            return "<a href='".env('APP_URL').'/public/storage/'.$url."' target='_blank'>".basename($url)."</a>";
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
            $nextStatuses = StatusTransition::where("table", Constant::SCORE_CARD_TABLE)->whereNull("status_id")->get();
            foreach ($nextStatuses as $nextStatus) {
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        }
        $form->select('contract_id', __('valuation_document.contract_id'))->options(Contract::where("branch_id", Admin::user()->branch_id)->where('status', Constant::CONTRACT_INPUTTING_STATUS)->pluck('code', 'id'));
        $form->text('property', __('Tài sản thẩm định giá'))->disable();
        $form->number('score', __('Điểm'));
        $form->select('error_score', __('Lỗi điểm'))->options(Constant::INVITATION_LETTERS_TYPE)->required();
        $form->file('document', __('Tài liệu'));
        $form->text('note', __('Ghi chú'));
        $form->select('status', __('Trạng thái'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
       
        // $url = 'http://127.0.0.1:8000/api/contract';
        $url = env('APP_URL') . '/api/contract';

        $script = <<<EOT
        $(document).on('change', ".contract_id", function () {
            $.get("$url",{q : this.value}, function (data) {
            $("#property").val(data.property)
        });
        });
        EOT;

        Admin::script($script);

        return $form;
    }
}
