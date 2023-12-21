<?php

namespace App\Admin\Controllers;

use App\Http\Models\Notification;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Actions\Document\ViewNotification;

class NotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Notification';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Notification());

        $grid->column('user.name', __('User nhận'));
        $grid->column('userSend.name', __('User gửi'));
        $grid->column('table', __('Bảng menu'));
        $grid->column('content', __('Nội dung'));
        $grid->column('table_id', __('Trình trạng'))->display(function () {
            return $this->check == 0 ? "Chưa xem" : "Đã xem";
        });
        $grid->column('check', __('Xem'))->action(ViewNotification::class);
        $grid->column('sent', __('Gửi'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->model()->where('user_id', Admin::user()->id);
        $grid->model()->orderBy('id', 'desc');
        $grid->disableActions();
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
        $show = new Show(Notification::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('user_id', __('User id'));
        $show->field('table', __('Table'));
        $show->field('check', __('Check'));
        $show->field('table_id', __('Table id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Notification());

        $form->number('user_id', __('User id'));
        $form->text('table', __('Table'));
        $form->number('check', __('Check'));
        $form->number('table_id', __('Table id'));

        return $form;
    }
}
