<?php

namespace App\Admin\Controllers;

use App\Http\Models\Status;
use App\Http\Models\StatusTransition;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StatusTransitionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'StatusTransition';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StatusTransition());

        $grid->column('status.name', __('Status id'));
        $grid->column('nextStatus.name', __('Next status id'));
        $grid->column('viewers', __('Viewers'));
        $grid->column('editors', __('Editors'));
        $grid->column('approvers', __('Approvers'));
        $grid->column('approve_type', __('Approve type'))->using(Constant::APPROVE_TYPE);
        $grid->column('table', __('Table'));

        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
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
        $show = new Show(StatusTransition::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('status_id', __('Status id'));
        $show->field('next_status_id', __('Next status id'));
        $show->field('viewers', __('Viewers'));
        $show->field('editors', __('Editors'));
        $show->field('table', __('Table'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StatusTransition());
        $form->select('status_id')->options(Status::selectRaw("CONCAT(`table`, ' - ',`name`) AS full_name, id")->pluck('full_name', 'id'));
        $form->select('next_status_id')->options(Status::selectRaw("CONCAT(`table`, ' - ',`name`) AS full_name, id")->pluck('full_name', 'id'));
        $form->multipleSelect('viewers', __('Viewers'))->options(Constant::ROLES);
        $form->multipleSelect('editors', __('Editors'))->options(Constant::ROLES);
        $form->multipleSelect('approvers', __('Approvers'))->options(Constant::ROLES);
        $form->select('approve_type', __('Approve Type'))->options(Constant::APPROVE_TYPE)->setWidth(5, 2);
        $form->select('table', __('Table'))->options(Constant::TABLES)->setWidth(5, 2)->required();

        return $form;
    }
}
