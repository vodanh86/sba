<?php

namespace App\Admin\Controllers;

use App\Http\Models\Contract;
use Encore\Admin\Controllers\AdminController;
use App\Http\Models\Status;
use Encore\Admin\Facades\Admin;
use App\Http\Models\StatusTransition;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class DoneContractController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hợp đồng đã hoàn thành';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $doneStatus = Status::where("table", "contracts")->where("done", 1)->first();
        $grid = new Grid(new Contract());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Tên khách hàng cá nhân/doanh nghiệp'));
        $grid->column('code', __('Mã hợp đồng'));
        $grid->column('invitationLetter.code', __('contract.Invitation letter id'));
        $grid->column('contact', __('Liên hệ'));
        $grid->column('note', __('Chú ý'));

        $grid->column('statusDetail.name',__('Trạng thái'))->width(100);
        $grid->model()->where('branch_id', '=', Admin::user()->branch_id)->where('status', $doneStatus->id);
        $grid->model()->orderBy('id', 'desc');
        if (Utils::getCreateRole(Constant::CONTRACT_TABLE) != Admin::user()->roles[0]->slug){
            $grid->disableCreateButton();
        }
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->column('comment', __('Bình luận'))->width(250);
        $grid->column('created_at', __('Ngày tạo'))->display(function ($createAt) {
            $carbonCreateAt = Carbon::parse($createAt);
            return $carbonCreateAt->format('d/m/Y - H:i:s');
        })->width(150);
        $grid->column('updated_at', __('Ngày cập nhật'))->display(function ($updatedAt) {
            $carbonUpdatedAt = Carbon::parse($updatedAt);
            return $carbonUpdatedAt->format('d/m/Y - H:i:s');
        })->width(150);
        // callback after save
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('code', 'Mã hợp đồng');
            $filter->like('invitationLetter.code', __('contract.Invitation letter id'));
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
        $show = new Show(Contract::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Tên khách hàng cá nhân/doanh nghiệp'));
        $show->field('code', __('Mã hợp đồng'));
        $show->field('invitation_letter_id', __('contract.Invitation letter id'));
        $show->field('contact', __('Liên hệ'));
        $show->field('note', __('Chú ý'));
        $show->field('statusDetail.name', __('Trạng thái'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Ngày cập nhật'));
        $show->panel()
        ->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });
        return $show;
    }
}
