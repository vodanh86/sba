<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\BaReport;
use Encore\Admin\Layout\Content;
use App\Admin\Forms\Report;
use App\Admin\Forms\AcReport;
use App\Admin\Forms\SaleReport;
use App\Admin\Forms\SupervisorReport;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Widgets\Tab;
use App\Http\Models\InvitationLetter;
use App\Http\Models\Contract;
use App\Http\Models\Status;
use App\Http\Models\Branch;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use App\Exports\ReportExport;
use App\Http\Models\AdminUser;
use App\Http\Models\OfficialAssessment;
use App\Http\Models\PreAssessment;
use App\Http\Models\ScoreCard;
use App\Http\Models\ValuationDocument;
use App\Http\Models\ContractAcceptance;
use Illuminate\Support\Facades\Config;
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
            ->title('Báo cáo')
            ->row(new SaleReport());

        if ($data = session('result')) {
            // If there is data returned from the backend, take it out of the session and display it at the bottom of the form
            if ($data["type"] == "l") {
                $headers = ['STT', 'Người tạo', 'Số lượng thư chào', 'Tổng phí dịch vụ', 'Chi nhánh'];
                if (session('result')['branch_id']) {
                    $query = InvitationLetter::where("branch_id", session('result')['branch_id']);
                } else {
                    $query = InvitationLetter::query();
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }
                $rows = [];
                $users = AdminUser::pluck("name", "id")->toArray();
                $sum = ["", "Tổng", 0, 0];
                $result = $query->select([
                    "user_id",
                    DB::raw("COUNT(*) as count"),
                    DB::raw("SUM(total_fee) as fee"),
                    "branch_id"
                ])->groupBy(["user_id", "branch_id"])->get();
                foreach ($result as $i => $row) {
                    $rows[] = [$i + 1, !is_null($row["user_id"]) && array_key_exists($row["user_id"], $users) ? $users[$row["user_id"]] : "", $row["count"], number_format($row["fee"]), Branch::find($row['branch_id'])->branch_name];
                    $sum[2] += $row["count"];
                    $sum[3] += $row["fee"];
                }
                $rows[] = $sum;
            } else if ($data["type"] == "c1") {
                $headers = ['STT', 'Nguồn', 'Loại hợp đồng', 'Tình trạng thực hiện', 'Số lượng hợp đồng', 'Tổng phí dịch vụ', 'Tổng doanh thu thuần', 'Chi nhánh'];
                $sources = array();
                $sum = [0, 0, 0];
                if (session('result')['branch_id']) {
                    $query = Contract::where("branch_id", session('result')['branch_id']);
                } else {
                    $query = Contract::query();
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }

                $query->where('status', "<>", Constant::OFFICIAL_CONTRACT_INIT);
                $result = $query->get();
                foreach ($result as $i => $row) {
                    $currentVal = array_key_exists($row["source"], $sources) ? $sources[$row["source"]] : [[[0, 0, 0], [0, 0, 0]], [[0, 0, 0], [0, 0, 0]], [[0, 0, 0]]];
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][0]++;
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][1] += $row["total_fee"];
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][2] += $row["net_revenue"];
                    $currentVal[2][0][0]++;
                    $currentVal[2][0][1] += $row["total_fee"];
                    $currentVal[2][0][2] += $row["net_revenue"];
                    $currentVal[3] = $row["branch_id"];
                    $sources[$row["source"]] = $currentVal;
                    $sum[0]++;
                    $sum[1] += $row["total_fee"];
                    $sum[2] += $row["net_revenue"];
                }

                $rows = [];
                $count = 1;
                foreach ($sources as $source => $row) {
                    $rows[] = [$count, $source, "Sơ bộ", "Đang xử lý", $row[0][0][0], number_format($row[0][0][1]), number_format($row[0][0][2]), Branch::find($row[3])->branch_name];
                    $rows[] = ["", "", "Sơ bộ", "Đã hoàn thành", $row[0][1][0], number_format($row[0][1][1]), number_format($row[0][1][2]), ""];
                    $rows[] = ["", "", "Chính thức", "Đang xử lý", $row[1][0][0], number_format($row[1][0][1]), number_format($row[1][0][2]), ""];
                    $rows[] = ["", "", "Chính thức", "Đã hoàn thành", $row[1][1][0], number_format($row[1][1][1]), number_format($row[1][1][2]), ""];
                    $rows[] = ["", "Tổng", "", "", $row[2][0][0], number_format($row[2][0][1]), number_format($row[2][0][2]), ""];
                    $count++;
                }
                $rows[] = ["", "Tổng cộng", "", "", $sum[0], number_format($sum[1]), number_format($sum[2])];
            } else {
                $headers = ['STT', 'Môi giới', 'Loại hợp đồng', 'Số lượng hợp đồng', 'Tổng phí dịch vụ', 'Tổng doanh thu thuần', 'Chi nhánh'];
                $brokers = array();
                $sum = [0, 0, 0];
                if (session('result')['branch_id']) {
                    $query = Contract::where("branch_id", session('result')['branch_id']);
                } else {
                    $query = Contract::query();
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }
                $query->where('status', "<>", 6);
                $result = $query->get();
                foreach ($result as $i => $row) {
                    $currentVal = array_key_exists($row["broker"], $brokers) ? $brokers[$row["broker"]] : [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
                    $currentVal[$row["contract_type"]][0]++;
                    $currentVal[$row["contract_type"]][1] += $row["total_fee"];
                    $currentVal[$row["contract_type"]][2] += $row["net_revenue"];
                    $currentVal[2][0]++;
                    $currentVal[2][1] += $row["total_fee"];
                    $currentVal[2][2] += $row["net_revenue"];
                    $currentVal[3] = $row["branch_id"];
                    $brokers[$row["broker"]] = $currentVal;
                    $sum[0]++;
                    $sum[1] += $row["total_fee"];
                    $sum[2] += $row["net_revenue"];
                }

                $rows = [];
                $count = 1;
                foreach ($brokers as $broker => $row) {
                    $rows[] = [$count, $broker, "Sơ bộ",  $row[0][0], number_format($row[0][1]), number_format($row[0][2]), Branch::find($row[3])->branch_name];
                    $rows[] = ["", "", "Chính thức", $row[1][0], number_format($row[1][1]), number_format($row[1][2]), ""];
                    $rows[] = ["", "Tổng", "", $row[2][0], number_format($row[2][1]), number_format($row[2][2]), ""];
                    $count++;
                }
                $rows[] = ["", "Tổng cộng", "",  $sum[0], number_format($sum[1]), number_format($sum[2])];
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'public/files/report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/storage/files/report.xlsx' target='_blank'>Link</a><br/><div class='report-result'>" . $table . '</div>');
            $content->row($tab);
        }

        return $content;
    }
}
