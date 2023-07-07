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
                $query = InvitationLetter::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))) {
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $headers = ['Số lượng', 'Tổng phí thẩm định'];
                $rows = [[$query->count(), number_format($query->sum('total_fee'))]];
            } else {
                $headers = ['Nguồn', 'Sale', 'Môi giới', 'Tình trạng thực hiện', 'Loại hợp đồng', 'Số lượng', 'Tổng phí dịch vụ'];
                $query = Contract::where("branch_id", Admin::user()->branch_id);
                if (!is_null(($data["from_date"]))) {
                    $query->where('created_at', '>=', $data["from_date"]);
                }
                if (!is_null(($data["to_date"]))) {
                    $query->where('created_at', '<=', $data["to_date"]);
                }
                $rows = [];
                $statuses = Status::pluck("name", "id")->toArray();
                $result = $query->select([
                    "source", "sale", "broker", "status", "contract_type",
                    DB::raw("COUNT(*) as count"),
                    DB::raw("SUM(total_fee) as fee")
                ])->groupBy(["source", "sale", "broker", "status", "contract_type"])->orderBy('sale')->orderBy('status')->get();
                foreach ($result as $i => $row) {
                    $rows[] = [
                        $row["source"], $row["sale"], $row["broker"],
                        !is_null($row["status"]) && array_key_exists($row["status"], $statuses) ? $statuses[$row["status"]] : "", is_null($row["contract_type"]) ? "" : Constant::CONTRACT_TYPE[$row["contract_type"]], $row["count"], number_format($row["fee"])
                    ];
                }
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/public/storage/report.xlsx' target='_blank'>Link</a><br/>" . $table);
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
            ->title('Báo cáo chứng thư phát hành')
            ->row(new BaReport());

        if ($data = session('result')) {
            // If there is data returned from the backend, take it out of the session and display it at the bottom of the form
            $headers = ['Số chứng thư', 
            'Ngày chứng thư', 
            'Thẩm định viên',
            'Đại diện pháp luật',
            'Khách hàng thẩm định giá', 
            'Tài sản thẩm định giá', 
            'Mục đích thẩm định giá',
            'Thời điểm thẩm định giá', 
            'Phương pháp thẩm định giá', 
            'Kết quả thẩm định giá', 
            // 'Tình trạng thực hiện', 
            // 'Số lượng', 
            'Phí dịch vụ'];
            $query = OfficialAssessment::where("branch_id", Admin::user()->branch_id);
            if (!is_null(($data["from_date"]))) {
                $query->where('created_at', '>=', $data["from_date"]);
            }
            if (!is_null(($data["to_date"]))) {
                $query->where('created_at', '<=', $data["to_date"]);
            } 
            $rows = [];
            $statuses = Status::pluck("name", "id")->toArray();
            $users = AdminUser::pluck("name", "id")->toArray();

            // $result = $query->select(["tdv_assistant", "official_assessments.code", "status", "contract_type", DB::raw("COUNT(*) as count")])->groupBy(["tdv_assistant", "official_assessments.code", "status", "contract_type"])
            // ->orderBy('tdv_assistant')->orderBy('status')->get();
            $result = $query->select(["certificate_code", 
            "certificate_date", 
            "official_assessments.contract_id",
            "official_assessments.contract_id",
            "official_assessments.contract_id",
            "official_assessments.contract_id", 
            "official_assessments.contract_id", 
            "official_assessments.contract_id", 
            "assessment_type", 
            "status",
            "official_assessments.contract_id", 
            DB::raw("COUNT(*) as count")])
                ->groupBy(["certificate_code", "certificate_date", "official_assessments.contract_id","official_assessments.contract_id","official_assessments.contract_id", "official_assessments.contract_id", "official_assessments.contract_id", "assessment_type", "status", "official_assessments.contract_id"])
                ->orderBy('performer')
                ->orderBy('status')
                ->get();


            foreach ($result as $i => $row) {
                $contract = $row->contract;
                $rows[] = [
                    isset($row["certificate_code"]) ? $row["certificate_code"] : $contract->code,
                    $row["certificate_date"],
                    $contract->tdv,
                    $contract->legal_representative,
                    $contract->name,
                    $contract->property,
                    $contract->purpose,
                    $contract->appraisal_date,
                    implode(", ", $row["assessment_type"]),
                    // !is_null($row["performer"]) && array_key_exists($row["performer"], $users) ? $users[$row["performer"]] : "", 
                    !is_null($row["status"]) && array_key_exists($row["status"], $statuses) ? $statuses[$row["status"]] : "",
                    number_format($contract->total_fee, 2, ',', ' ') . " VND",
                ];
            }

            $table = new Table($headers, $rows);
            $tab = new Tab();

            // store in excel
            array_unshift($rows, $headers);
            $export = new ReportExport($rows);
            Excel::store($export, 'report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/public/storage/report.xlsx' target='_blank'>Link</a><br/>" . $table);
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
            Excel::store($export, 'report.xlsx');

            $tab->add('Kết quả', "<b>Từ ngày: </b>" . $data['from_date'] . " <b> Đến ngày: </b> " . $data["to_date"] .
                "<br/>Link download: <a href='" . env('APP_URL') . "/public/storage/report.xlsx' target='_blank'>Link</a><br/>" . $table);
            $content->row($tab);
        }

        return $content;
    }
}
