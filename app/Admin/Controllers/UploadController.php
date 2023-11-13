<?php

namespace App\Admin\Controllers;

use Encore\Admin\Layout\Content;
use App\Admin\Forms\UploadForm;
use Encore\Admin\Widgets\Table;
use App\Http\Models\Price;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;

class UploadController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Tải báo giá';

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        $content
            ->title('Bảng giá')
            ->row(new UploadForm());

        if ($data = session('result')) {
            $headers = ['STT', 'Người tạo', 'Số lượng thư chào', 'Tổng phí dịch vụ', 'Chi nhánh'];
            foreach($data['rows'] as $i => $row){
                $price = new Price();
                $price->province = $row[1];
                $price->district = $row[2];
                $price->street = $row[3];
                $price->from = $row[4];
                $price->to = $row[5];
                $price->location = $row[6];
                $price->type = $row[7];
                $price->from_price = $row[8];
                $price->to_price = $row[9];
                $price->note = $row[10];
                $price->expired_date = Carbon::parse($data["expired_date"])->format('d/m/y');
                $price->created_at = new Carbon;
                $price->updated_at = new Carbon;
                $price->save();
            }
        }

        $grid = new Grid(new Price());
        $grid->column('id', __('Id'));
        $grid->column('province', __('Tỉnh/Thành phố'))->filter('like');
        $grid->column('district', __('Quận/Huyện'))->filter('like');
        $grid->column('street', __('Đường'))->filter('like');
        $grid->column('from', __('Từ'))->filter('like');
        $grid->column('expired_date', __('Ngày hết hạn'))->filter('like');
        $grid->model()->orderBy('id', 'desc');

        $content->row($grid);

        return $content;
    }
}
