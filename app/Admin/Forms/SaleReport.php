<?php

namespace App\Admin\Forms;

use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class SaleReport extends Form
{
    /**
     * The form title.
     *
     * @var  string
     */
    public $title = 'Thông tin';

    /**
     * Handle the form request.
     *
     * @param  Request $request
     *
     * @return  \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {
        $result = array("from_date" => $request->get("from_date"),
                        "to_date" => $request->get("to_date"),
                        "type" => $request->get("type"),
                        "excel" => $request->get("excel")[0]);
        return back()->with(['result' => $result]);
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->date('from_date', 'Từ ngày')->width(2);
        $this->date('to_date', 'Đến ngày')->width(2);
        $this->radio('type', 'Loại báo cáo')->options(['l' => 'Thư chào ', 'c'=> ' Hợp đồng'])->default('l');
        $this->checkbox('excel', 'Xuất excel')->options([1 => 'Có']);
    }

    /**
     * The data of the form.
     *
     * @return  array $data
     */
    public function data()
    {
        return [
            'from_date' => date('Y-m'),
            'to_date' => date("Y-m-d"),
        ];
    }
}