<?php

namespace App\Admin\Forms;

use Encore\Admin\Facades\Admin;
use Maatwebsite\Excel\Facades\Excel;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Imports\PricesImport;
use Config;

class UploadForm extends Form
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
        $expiredDate = Carbon::createFromFormat('d-m-Y', $request->get("expired_date"))->timezone(Config::get('app.timezone'));
        $sheets = Excel::toArray(new PricesImport, request()->file('file'));
        $rows = [];
        $error = "";
        foreach ($sheets as $i => $sheet) {
            foreach ($sheet as $j => $row) {
                if ($j > 1) {
                    if ($row[1] && $row[2] && $row[3] && $row[4] && $row[5] && $row[6] && $row[7] && $row[8] && $row[9]) {
                        $rows[] = $row;
                    } else {
                        $error .= "Lỗi thiếu thông tin ở dòng $j </br>";
                    }
                }
            }
            break;
        }
        if ($error && false) {
            admin_error("Lỗi", $error);
            return back();
        } else {
            $result = array(
                "expired_date" => $request->get("expired_date"),
                "rows" => $rows
            );
            return back()->with(['result' => $result]);
        }
    }
    /**
     * Build a form here.
     */
    public function form()
    {
        $this->date('expired_date', 'Ngày hết hạn')->format('DD-MM-YYYY')->width(2)->required();
        $this->file('file', 'Upload file')->rules('mimes:xls,xlsx')->width(2)->required();
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