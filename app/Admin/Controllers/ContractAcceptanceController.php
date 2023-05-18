<?php

namespace App\Admin\Controllers;

use App\Http\Models\ContractAcceptance;
use App\Http\Models\Contract;
use App\Http\Models\StatusTransition;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ContractAcceptanceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'ContractAcceptance';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $nextStatuses = Utils::getNextStatuses(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug);
        $viewStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "viewers");
        $editStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "editors");
        $approveStatus = Utils::getAvailbleStatus(Constant::CONTRACT_ACCEPTANCE_TABLE, Admin::user()->roles[0]->slug, "approvers");
        $grid = new Grid(new ContractAcceptance());

        $grid->column('id', __('Id'));
        $grid->column('contract.name', __('Contract id'));
        $grid->column('document', __('Document'))->display(function ($url) {
            return "<a href='".env('APP_URL').'/../storage/app/'.$url."' target='_blank'>".basename($url)."</span>";
        });
        $grid->column('address', __('Address'));
        $grid->column('total_fee', __('Total fee'));
        $grid->column('delivery', __('Delivery'));
        $grid->column('recipient', __('Recipient'));
        $grid->column('status')->display(function ($statusId, $column) use ($approveStatus, $nextStatuses) {
            if (in_array($statusId, $approveStatus) == 1) {
                return $column->editable('select', $nextStatuses);
            }
            return $this->statusDetail->name;
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
        $show = new Show(ContractAcceptance::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('branch_id', __('Branch id'));
        $show->field('contract_id', __('Contract id'));
        $show->field('status', __('Status'));
        $show->field('address', __('Address'));
        $show->field('total_fee', __('Total fee'));
        $show->field('delivery', __('Delivery'));
        $show->field('recipient', __('Recipient'));
        $show->field('document', __('Document'));
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

        $form = new Form(new ContractAcceptance());
        $status = array();
        if ($form->isEditing()) {
            $id = request()->route()->parameter('contract_acceptance');
            $model = $form->model()->find($id);
            $currentStatus = $model->status;
            $nextStatuses = StatusTransition::where(["table" => Constant::CONTRACT_ACCEPTANCE_TABLE, "status_id" => $currentStatus])->where('editors', 'LIKE', '%'.Admin::user()->roles[0]->slug.'%')->get();
            $status[$model->status] = $model->statusDetail->name;
            foreach($nextStatuses as $nextStatus){
                $status[$nextStatus->next_status_id] = $nextStatus->nextStatus->name;
            }
        } else {
            $nextStatuses = StatusTransition::where("table", Constant::CONTRACT_ACCEPTANCE_TABLE)->whereNull("status_id")->first();
            $status[$nextStatuses->next_status_id] = $nextStatuses->nextStatus->name;
        }
        $form->select('contract_id')->options(Contract::where("branch_id", Admin::user()->branch_id)->pluck('name', 'id'));
        $form->text('address', __('Address'));
        $form->number('total_fee', __('Total fee'));
        $form->text('delivery', __('Delivery'));
        $form->text('recipient', __('Recipient'));
        $form->file('document', __('Document'));
        $form->select('status', __('Status'))->options($status)->setWidth(5, 2)->required();
        $form->hidden('branch_id')->default(Admin::user()->branch_id);

        return $form;
    }
}
