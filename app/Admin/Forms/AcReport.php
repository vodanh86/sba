<?php

namespace App\Admin\Forms;

use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\Constant;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Models\Branch;
use Config;

class AcReport extends Form
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
        $fromDate = Carbon::createFromFormat('d-m-Y', $request->get("from_date"))->timezone(Config::get('app.timezone'));
        $toDate = Carbon::createFromFormat('d-m-Y', $request->get("to_date"))->timezone(Config::get('app.timezone'));
        $result = array("from_date" => $request->get("from_date"),
                        "to_date" => $request->get("to_date"),
                        "branch_id" => $request->get("branch_id"),
                        "formated_from_date" => $fromDate->format('Y-m-d'),
                        "formated_to_date" => $toDate->format('Y-m-d 23:59:59'),
                        "type" => $request->get("type"));
        return back()->with(['result' => $result]);
    }
    /**
     * Build a form here.
     */
    public function form()
    {
        $this->date('from_date', 'Từ ngày')->format('DD-MM-YYYY')->width(2);
        $this->date('to_date', 'Đến ngày')->format('DD-MM-YYYY')->width(2);
        if (Admin::user()->isRole(Constant::DIRECTOR_ROLE)){
            $this->select('branch_id', 'Chi nhánh')->options(Branch::all()->pluck('branch_name', 'id'))->width(2);
        } else {
            $this->select('branch_id', 'Chi nhánh')->options(Branch::all()->pluck('branch_name', 'id'))->default(Admin::user()->branch_id)->readonly();
        }
        $this->radio('type', 'Loại báo cáo')->options(['c' => 'Báo cáo chứng thư ', 'v'=> 'Báo cáo hồ sơ thẩm định'])->default('c');
    }

    /**
     * The data of the form.
     *
     * @return  array $data
     */
    public function data()
    {
        if ($data = session('result')) {
            return $data;
        }
        return [
            'from_date' => date('01-m-Y'),
            'to_date' => date("d-m-Y"),
        ];
    }
}