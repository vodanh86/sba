<?php

namespace App\Admin\Controllers;

use App\Http\Models\QrCode;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

class QrCodeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Quản lý mã QR';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new QrCode());

        $grid->column('id', __('Id'));
        $grid->column('contract_code', __('Mã hợp đồng'))->filter('like');
        $grid->column('pin_code', __('Mã pin'))->filter('like');
        $grid->column('expiration_date', __('Ngày hết hạn'));
        return $grid;
    }
}
