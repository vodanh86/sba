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
        $viewStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::SCORE_CARD_TABLE, Admin::user()->roles[0]->slug, "approvers");

        $grid = new Grid(new ScoreCard());
        $grid->column('id', __('Id'));
        $grid->column('contract.name', __('Contract id'));
        $grid->column('score', __('Score'));
        $grid->column('document', __('Document'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</span>";
        });

        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->whereIn('status', array_merge($viewStatus, $editStatus, $approveStatus));
        $grid->actions(function ($actions) use ($editStatus, $grid) {
            if (!in_array($actions->row->status, $editStatus)) {
                $grid->disableCreateButton();
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
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('branch_id', __('Branch id'));
        $show->field('contract_id', __('Contract id'));
        $show->document()->file();
        $show->field('status', __('Status'));
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
        $nextStatuses = StatusTransition::where("table", Constant::SCORE_CARD_TABLE)->whereNull("status_id")->first();
        $form = new Form(new ScoreCard());

        $form->select('contract_id')->options(Contract::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->number('score', __('Score'));
        $form->file('document', __('Document'));
        $form->hidden('branch_id')->default(Admin::user()->branch_id);
        $form->hidden('status')->default($nextStatuses->next_status_id);

        return $form;
    }
}
