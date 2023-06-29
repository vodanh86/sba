<?php

namespace App\Admin\Controllers;

use Encore\Admin\Layout\Content;
use App\Admin\Forms\SaleReport;
Use Encore\Admin\Widgets\Table;
use Encore\Admin\Widgets\Tab;
use App\Http\Models\InvitationLetter;
use App\Http\Models\Contract;
use App\Http\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class ReportController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Báo cáo';

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function saleReport(Content $content)
    {
        $content
            ->title('Báo cáo')
            ->row(new SaleReport());

        if ($data = session('result')) {
            // If there is data returned from the backend, take it out of the session and display it at the bottom of the form
            if ($data["type"] == "l") {
                $query = InvitationLetter::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))){
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))){
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $headers = ['Số lượng', 'Tổng phí thẩm định'];
                $rows = [[$query->count(), number_format($query->sum('total_fee'))]];
            } else {
                $headers = ['Nguồn', 'Sale', 'Môi giới', 'Tình trạng thực hiện', 'Loại hợp đồng', 'Số lượng', 'Tổng phí dịch vụ'];
                $query = Contract::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))){
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))){
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $rows = [];
                $statuses = Status::pluck("name", "id")->toArray();
                $result = $query->select(["source", "sale", "broker", "status", "contract_type",
                DB::raw("COUNT(*) as count"),
                DB::raw("SUM(total_fee) as fee")])->groupBy(["source", "sale", "broker", "status", "contract_type"])->get();
                foreach($result as $i=>$row){
                    $rows[] = [$row["source"], $row["sale"], $row["broker"], 
                    !is_null($row["status"]) && array_key_exists($row["status"], $statuses) ? $statuses[$row["status"]] : "", is_null($row["contract_type"]) ? "" : Constant::CONTRACT_TYPE[$row["contract_type"]], $row["count"], number_format($row["fee"])];
                }
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                    "<br/>Link download: <a href='".env('APP_URL')."/../storage/app/report.xlsx' target='_blank'>Link</a><br/>" . $table);
            $content->row($tab);
            
        }

        return $content;
    }
}
