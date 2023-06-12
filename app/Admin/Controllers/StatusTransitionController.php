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

        $grid->column('status.name', __('Id trạng thái'));
        $grid->column('nextStatus.name', __('Id trạng thái tiếp'));
        $grid->column('viewers', __('Những người xem'));
        $grid->column('editors', __('Những người chỉnh sửa'));
        $grid->column('approvers', __('Những người duyệt'));
        $grid->column('approve_type', __('Loại hình duyệt'))->using(Constant::APPROVE_TYPE);
        $grid->column('table', __('Bảng'))->filter(Constant::TABLES);

        $grid->column('created_at', __('Ngày tạo'));
        $grid->column('updated_at', __('Ngày cập nhật'));
        $grid->model()->orderBy('id', 'desc');
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
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        $show->field('status_id', __('Id trạng thái'));
        $show->field('next_status_id', __('Id trạng thái tiếp'));
        $show->field('viewers', __('Những người xem'));
        $show->field('editors', __('Những người chỉnh sửa'));
        $show->field('table', __('Bảng'));

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
        $form->multipleSelect('viewers', __('Những người xem'))->options(Constant::ROLES);
        $form->multipleSelect('editors', __('Những người chỉnh sửa'))->options(Constant::ROLES);
        $form->multipleSelect('approvers', __('Những người duyệt'))->options(Constant::ROLES);
        $form->select('approve_type', __('Loại hình duyệt'))->options(Constant::APPROVE_TYPE)->setWidth(5, 2);
        $form->select('table', __('Bảng'))->options(Constant::TABLES)->setWidth(5, 2)->required();

        return $form;
    }
}
