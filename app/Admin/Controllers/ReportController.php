<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\BaReport;
use Encore\Admin\Layout\Content;
use App\Admin\Forms\SaleReport;
use App\Admin\Forms\SupervisorReport;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Widgets\Tab;
use App\Http\Models\InvitationLetter;
use App\Http\Models\Contract;
use App\Http\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use App\Exports\ReportExport;
use App\Http\Models\AdminUser;
use App\Http\Models\OfficialAssessment;
use App\Http\Models\PreAssessment;
use App\Http\Models\ScoreCard;
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
                $headers = ['Người tạo', 'Số lượng thư chào', 'Tổng phí dịch vụ'];
                $query = InvitationLetter::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))) {
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $rows = [];
                $users = AdminUser::pluck("name", "id")->toArray();
                $sum = ["Tổng", 0, 0];
                $result = $query->select([
                    "user_id",
                    DB::raw("COUNT(*) as count"),
                    DB::raw("SUM(total_fee) as fee")
                ])->groupBy(["user_id"])->get();
                foreach ($result as $i => $row) {
                    $rows[] = [!is_null($row["user_id"]) && array_key_exists($row["user_id"], $users) ? $users[$row["user_id"]] : "", $row["count"], number_format($row["fee"])];
                    $sum[1] += $row["count"];
                    $sum[2] += $row["fee"];
                }
                $rows[] = $sum;
            } else if($data["type"] == "c1") {
                $headers = ['Sale','Loại hợp đồng', 'Tình trạng thực hiện', 'Số lượng hợp đồng', 'Tổng phí dịch vụ'];
                $sales = array();
                $sum = [0, 0];
                $query = Contract::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))) {
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $result = $query->get();
                foreach ($result as $i => $row) {
                    $currentVal = array_key_exists($row["sale"], $sales) ? $sales[$row["sale"]] : [[[0,0], [0,0]], [[0,0], [0,0]], [[0,0]]];
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][0] ++;
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][1] += $row["total_fee"];
                    $currentVal[2][0][0] ++ ;
                    $currentVal[2][0][1] += $row["total_fee"];
                    $sales[$row["sale"]] = $currentVal;
                    $sum[0] ++;
                    $sum[1] += $row["total_fee"];
                }

                $rows = [];
                foreach ($sales as $sale => $row) {
                    $rows[] = [$sale, "Sơ bộ", "Đang xử lý", $row[0][0][0], number_format($row[0][0][1])];
                    $rows[] = ["", "Sơ bộ", "Đã hoàn thành", $row[0][1][0], number_format($row[0][1][1])];
                    $rows[] = ["", "Chính thức", "Đang xử lý", $row[1][0][0], number_format($row[1][0][1])];
                    $rows[] = ["", "Chính thức", "Đã hoàn thành", $row[1][1][0], number_format($row[1][1][1])];
                    $rows[] = ["Tổng", "", "", $row[2][0][0], number_format($row[2][0][1])];
                }
                $rows[] = ["Tổng cộng", "", "", $sum[0], number_format($sum[1])];
            } else {
                $headers = ['Môi giới','Loại hợp đồng', 'Số lượng hợp đồng', 'Tổng phí dịch vụ'];
                $brokers = array();
                $sum = [0, 0];
                $query = Contract::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))) {
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $result = $query->get();
                foreach ($result as $i => $row) {
                    $currentVal = array_key_exists($row["broker"], $brokers) ? $brokers[$row["broker"]] : [[0,0], [0,0], [0,0]];
                    $currentVal[$row["contract_type"]][0] ++;
                    $currentVal[$row["contract_type"]][1] += $row["total_fee"];
                    $currentVal[2][0] ++ ;
                    $currentVal[2][1] += $row["total_fee"];
                    $brokers[$row["broker"]] = $currentVal;
                    $sum[0] ++;
                    $sum[1] += $row["total_fee"];
                }

                $rows = [];
                foreach ($brokers as $broker => $row) {
                    $rows[] = [$broker, "Sơ bộ",  $row[0][0], number_format($row[0][1])];
                    $rows[] = ["", "Chính thức", $row[1][0], number_format($row[1][1])];
                    $rows[] = ["Tổng", "", $row[2][0], number_format($row[2][1])];
                }
                $rows[] = ["Tổng cộng", "",  $sum[0], number_format($sum[1])];
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'public/files/report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/storage/files/report.xlsx' target='_blank'>Link</a><br/>" . $table);
            $content->row($tab);
        }

        return $content;
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function baReport(Content $content)
    {
        $content
            ->title('Báo cáo')
            ->row(new BaReport());

        if ($data = session('result')) {
            // If there is data returned from the backend, take it out of the session and display it at the bottom of the form
            $headers = ['Mã hợp đồng', 'Môi giới', 'Tài sản thẩm định giá', 'Mục đích thẩm định giá', 'Tình trạng thực hiện','Ngày hoàn thành'];
            $query = PreAssessment::where("branch_id", Admin::user()->branch_id);
            if (!is_null(($data["from_date"]))) {
                $query->where('created_at', '>=', $data["from_date"]);
            }
            if (!is_null(($data["to_date"]))) {
                $query->where('created_at', '<=', $data["to_date"]);
            }
            $rows = [];
            $query = Contract::where("branch_id", Admin::user()->branch_id)->where("contract_type", $data["type"] == "prev" ? Constant::PRE_CONTRACT_TYPE : Constant::OFFICIAL_CONTRACT_TYPE )
            ->where("tdv", Admin::user()->id);
            if (!is_null(($data["from_date"]))) {
                $query->where('created_at', '>=', $data["from_date"]);
            }
            if (!is_null(($data["to_date"]))) {
                $query->where('created_at', '<=', $data["to_date"]);
            }
            $result = $query->get();
            $rows = [];
            foreach ($result as $row) {
                if (Utils::checkContractStatus($row) == 0){
                    $rows[] = [
                        $row->code,
                        $row->broker,
                        $row->property,
                        $row->purpose,
                        "Đang xử lý",
                        ""
                    ];
                } else {
                    $endDate = "";
                    $rows[] = [
                        $row->code,
                        $row->broker,
                        $row->property,
                        $row->purpose,
                        "Đã hoàn thành",
                        Utils::checkContractEndDate($row)
                    ];
                }
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'public/files/report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/storage/files/report.xlsx' target='_blank'>Link</a><br/>" . $table);
            $content->row($tab);
        }

        return $content;
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function supervisorReport(Content $content)
    {
        $content
            ->title('Báo cáo')
            ->row(new SupervisorReport());

        if ($data = session('result')) {
            // If there is data returned from the backend, take it out of the session and display it at the bottom of the form
            if ($data["type"] == "l") {
                $headers = ['Nhân viên', 'Loại hợp đồng', 'Số lượng'];
                $query = Contract::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))) {
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $rows = [];
                $statuses = Status::pluck("name", "id")->toArray();
                $users = AdminUser::pluck("name", "id")->toArray();
                $result = $query->select(["supervisor", "contract_type", DB::raw("COUNT(*) as count")])->groupBy(["supervisor", "contract_type"])->orderby('supervisor')->orderBy('contract_type')->get();
                foreach ($result as $i => $row) {
                    $rows[] = [
                        !is_null($row["supervisor"]) && array_key_exists($row["supervisor"], $users) ? $users[$row["supervisor"]] : "",
                        is_null($row["contract_type"]) ? "" : Constant::CONTRACT_TYPE[$row["contract_type"]], $row["count"]
                    ];
                }
            } else {
                $headers = ['Nhân viên', 'Lỗi', 'Điểm', 'Số lượng'];
                $rows = [];
                $statuses = Status::pluck("name", "id")->toArray();
                $users = AdminUser::pluck("name", "id")->toArray();
                $result = DB::select("SELECT " .
                    "sba.contracts.tdv_assistant, " .
                    "sba.score_cards.error_score, " .
                    "score, " .
                    "COUNT(*) AS count " .
                    "FROM " .
                    "sba.score_cards, " .
                    "sba.contracts " .
                    "WHERE " .
                    "sba.score_cards.contract_id = sba.contracts.id " .
                    "AND sba.score_cards.branch_id = ? " .
                    "AND sba.score_cards.created_at >= '" . $data["from_date"] . "' " .
                    "AND sba.score_cards.created_at <= '" . $data["to_date"] . "' " .
                    "GROUP BY sba.contracts.tdv_assistant , sba.score_cards.error_score , sba.score_cards.score " .
                    "ORDER BY sba.contracts.tdv_assistant , sba.score_cards.score;", array(Admin::user()->branch_id));
                foreach ($result as $i => $row) {
                    $rows[] = [
                        !is_null($row->tdv_assistant) && array_key_exists($row->tdv_assistant, $users) ? $users[$row->tdv_assistant] : "",
                        $row->error_score, $row->score, $row->count
                    ];
                }
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'public/files/report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/storage/files/report.xlsx' target='_blank'>Link</a><br/>" . $table);
            $content->row($tab);
        }

        return $content;
    }
}
