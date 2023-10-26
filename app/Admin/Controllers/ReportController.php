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
                $headers = ['STT', 'Sale', 'Loại hợp đồng', 'Tình trạng thực hiện', 'Số lượng hợp đồng', 'Tổng phí dịch vụ', 'Tổng doanh thu thuần', 'Chi nhánh'];
                $sales = array();
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
                    $currentVal = array_key_exists($row["sale"], $sales) ? $sales[$row["sale"]] : [[[0, 0, 0], [0, 0, 0]], [[0, 0, 0], [0, 0, 0]], [[0, 0, 0]]];
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][0]++;
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][1] += $row["total_fee"];
                    $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)][2] += $row["net_revenue"];
                    $currentVal[2][0][0]++;
                    $currentVal[2][0][1] += $row["total_fee"];
                    $currentVal[2][0][2] += $row["net_revenue"];
                    $currentVal[3] = $row["branch_id"];
                    $sales[$row["sale"]] = $currentVal;
                    $sum[0]++;
                    $sum[1] += $row["total_fee"];
                    $sum[2] += $row["net_revenue"];
                }

                $rows = [];
                $count = 1;
                foreach ($sales as $sale => $row) {
                    $rows[] = [$count, $sale, "Sơ bộ", "Đang xử lý", $row[0][0][0], number_format($row[0][0][1]), number_format($row[0][0][2]), Branch::find($row[3])->branch_name];
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
            $headers = ['STT', 'Mã hợp đồng', 'Môi giới', 'Tài sản thẩm định giá', 'Mục đích thẩm định giá', 'Chuyên viên nghiệp vụ', 'Tình trạng thực hiện', 'Ngày hoàn thành', 'Chi nhánh'];
            if($data["type"] == "prev"){
                if (session('result')['branch_id']) {
                    $query = Contract::where("branch_id", session('result')['branch_id'])->where("contract_type", Constant::PRE_CONTRACT_TYPE);
                } else {
                    $query = Contract::where("contract_type", Constant::PRE_CONTRACT_TYPE);
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }
            }else{
                if (session('result')['branch_id']) {
                    $query = Contract::where("branch_id", session('result')['branch_id'])->where("contract_type", Constant::OFFICIAL_CONTRACT_TYPE);
                } else {
                    $query = Contract::where("contract_type", Constant::OFFICIAL_CONTRACT_TYPE);
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }
            }
            $result = $query->get();
            $rows = [];
            $count = 1;
            foreach ($result as $row) {
                $dateFormatter = function ($type, $contractId) {
                    $finishedDate = null;
                    if ($type == "prev") {
                        $preAssessment = PreAssessment::where("contract_id", "=", $contractId)->first();
                        if ($preAssessment) {
                            $finishedDate = $preAssessment->finished_date;
                        }
                    } else {
                        $officialAssessment = OfficialAssessment::where("contract_id", "=", $contractId)->first();
                        if ($officialAssessment) {
                            $finishedDate = $officialAssessment->finished_date;
                        }
                    }
                
                    if ($finishedDate) {
                        $carbonFinishedDate = Carbon::parse($finishedDate)->timezone(Config::get('app.timezone'));
                        return $carbonFinishedDate->format('d/m/Y');
                    }
                    return "";
                };
                if($data["type"] == "prev"){
                    $preAssessment = PreAssessment::where("contract_id", "=", $row->id)->first();
                    if ($preAssessment) {
                        $status = Status::where("id", "=", $preAssessment->status)->where("table", "pre_assessments")->first()->done;
                        if($status == 1){
                            $status = "Hoàn thành";
                        }else{
                            $status = "Chưa hoàn thành";
                        }
                    } else {
                        $status = "Chưa giao nhiệm vụ";
                    }
                }else{
                    $officialAssessment = OfficialAssessment::where("contract_id", $row->id)->first();
                    if ($officialAssessment) {
                        $status = Status::where("id", "=", $officialAssessment->status)->where("table", "official_assessments")->first()->done;
                        if($status == 1){
                            $status = "Hoàn thành";
                        }else{
                            $status = "Chưa hoàn thành";
                        }
                    } else {
                        $status = "Chưa giao nhiệm vụ";
                    }
                }
                $convertIdToNameUser = AdminUser::find($row->tdv_assistant);
                if ($convertIdToNameUser) {
                    $name = $convertIdToNameUser->name;
                } else {
                    $name = null;
                }
                $rows[] = [
                    $count,
                    $row->code,
                    $row->broker,
                    $row->property,
                    $row->purpose,
                    $name,
                    $status,
                    $dateFormatter($data["type"], $row->id),
                    Branch::find($row->branch_id)->branch_name
                ];
                $count++;
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

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function baManagerReport(Content $content)
    {
        $content
            ->title('Báo cáo')
            ->row(new Report());

        if ($data = session('result')) {
            $headers = ['STT', 'Người thực hiện', 'Loại hợp đồng', 'Tình trạng thực hiện', 'Số lượng hợp đồng', 'Chi nhánh'];
            $users = AdminUser::pluck("name", "id")->toArray();
            $branches = AdminUser::pluck("branch_id", "id")->toArray();
            $appraisers = array();
            $sum = 0;
            if (session('result')['branch_id']) {
                $query = Contract::where("branch_id", session('result')['branch_id'])->where('status', "<>", Constant::OFFICIAL_CONTRACT_INIT);
            } else {
                $query = Contract::where('status', "<>", Constant::OFFICIAL_CONTRACT_INIT);
            }
            if (!is_null(($data["formated_from_date"]))) {
                $query->where('created_at', '>=', $data["formated_from_date"]);
            }
            if (!is_null(($data["formated_to_date"]))) {
                $query->where('created_at', '<=', $data["formated_to_date"]);
            }
            $result = $query->get();
            
            foreach ($result as $i => $row) {
                $currentVal = array_key_exists($row["tdv_assistant"], $appraisers) ? $appraisers[$row["tdv_assistant"]] : [[0, 0], [0, 0], 0];
                $currentVal[$row["contract_type"]][Utils::checkContractStatus($row)]++;
                $currentVal[2]++;
                $appraisers[$row["tdv_assistant"]] = $currentVal;
                $sum++;
            }

            $rows = [];
            $tmpRows = [];
            $count = 0;
            foreach ($appraisers as $appraiser => $row) {
                if (array_key_exists($appraiser, $users)) {
                    $count++;
                    $rows[] = [$count, array_key_exists($appraiser, $users) ? $users[$appraiser] : $appraiser, "Sơ bộ",  "Đang xử lý", $row[0][0], array_key_exists($appraiser, $branches) ? Branch::find($branches[$appraiser])->branch_name : ""];
                    $rows[] = ["", "", "Sơ bộ", "Đã hoàn thành", number_format($row[0][1])];
                    $rows[] = ["", "", "Chính thức", "Đang xử lý", number_format($row[1][0])];
                    $rows[] = ["", "", "Chính thức", "Đã hoàn thành", number_format($row[1][1])];
                    $rows[] = ["", "Tổng", "", "", $row[2]];
                } else {
                    $tmpRows[] = [$count, array_key_exists($appraiser, $users) ? $users[$appraiser] : $appraiser, "Sơ bộ",  "Đang xử lý", $row[0][0], array_key_exists($appraiser, $branches) ? Branch::find($branches[$appraiser])->branch_name : ""];
                    $tmpRows[] = ["", "", "Sơ bộ", "Đã hoàn thành", number_format($row[0][1])];
                    $tmpRows[] = ["", "", "Chính thức", "Đang xử lý", number_format($row[1][0])];
                    $tmpRows[] = ["", "", "Chính thức", "Đã hoàn thành", number_format($row[1][1])];
                    $tmpRows[] = ["", "Tổng", "", "", $row[2]];
                }
            }
            if (count($tmpRows) > 0) {
                $tmpRows[0][0] = $count + 1;
                $rows = array_merge($rows, $tmpRows);
            }
            $rows[] = ["", "Tổng cộng", "",  "", $sum];

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
            $headers = ['STT', 'Người thực hiện', 'Số lượng hợp đồng chính thức', 'Số lỗi cơ bản', 'Số lỗi nghiệp vụ', 'Số lỗi nghiêm trọng', 'Số điểm', 'Chi nhánh'];
            $rows = [];
            $users = AdminUser::pluck("name", "id")->toArray();
            if (session('result')['branch_id']) {
                $result = DB::select("SELECT " .
                "sba.contracts.tdv_assistant, " .
                "COUNT(*) AS count, " .
                "SUM(sba.score_cards.score) AS score, " .
                "SUM(sba.score_cards.basic_error) AS basic_error, " .
                "SUM(sba.score_cards.business_error) AS business_error, " .
                "SUM(sba.score_cards.serious_error) AS serious_error,
                sba.contracts.branch_id " .
                "FROM " .
                "sba.score_cards, " .
                "sba.contracts " .
                "WHERE " .
                "sba.score_cards.contract_id = sba.contracts.id " .
                "AND sba.score_cards.branch_id = ? " .
                "AND sba.score_cards.created_at >= '" . $data["formated_from_date"] . "' " .
                "AND sba.score_cards.created_at <= '" . $data["formated_to_date"] . "' " .
                "GROUP BY sba.contracts.tdv_assistant, sba.contracts.branch_id;", array(session('result')['branch_id']));
            } else {
                $result = DB::select("SELECT " .
                "sba.contracts.tdv_assistant, " .
                "COUNT(*) AS count, " .
                "SUM(sba.score_cards.score) AS score, " .
                "SUM(sba.score_cards.basic_error) AS basic_error, " .
                "SUM(sba.score_cards.business_error) AS business_error, " .
                "SUM(sba.score_cards.serious_error) AS serious_error, " .
                "sba.contracts.branch_id " .
                "FROM " .
                "sba.score_cards, " .
                "sba.contracts " .
                "WHERE " .
                "sba.score_cards.contract_id = sba.contracts.id " .
                "AND sba.score_cards.created_at >= '" . $data["formated_from_date"] . "' " .
                "AND sba.score_cards.created_at <= '" . $data["formated_to_date"] . "' " .
                "GROUP BY sba.contracts.tdv_assistant, sba.contracts.branch_id;");
            }
            foreach ($result as $i => $row) {
                $rows[] = [
                    $i + 1,
                    !is_null($row->tdv_assistant) && array_key_exists($row->tdv_assistant, $users) ? $users[$row->tdv_assistant] : "",
                    $row->count, is_null($row->basic_error) ? 0 : $row->basic_error, is_null($row->business_error) ? 0 : $row->business_error,
                    is_null($row->serious_error) ? 0 : $row->serious_error, $row->score, Branch::find($row->branch_id)->branch_name
                ];
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

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function accountantManagerReport(Content $content)
    {
        $content
            ->title('Báo cáo chứng thư phát hành')
            ->row(new AcReport());

        if ($data = session('result')) {
            if ($data["type"] == "c") {
                $headers = ['STT', 'Số chứng thư', 'Ngày chứng thư', 'Thẩm định viên', 'Đại diện pháp luật', 'Tài sản thẩm định giá', 'Mục đích thẩm định giá', 'Thời điểm thẩm định gía', 'Phương pháp thẩm định giá', 'Kết quả thẩm định giá', 'Người thực hiện', 'Chi nhánh'];
                $users = AdminUser::pluck("name", "id")->toArray();
                if (session('result')['branch_id']) {
                    $query = ContractAcceptance::where("branch_id", session('result')['branch_id'])->where("status", 35);
                } else {
                    $query = ContractAcceptance::query();
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }
                $result = $query->get();
                $rows = [];
                foreach ($result as $i => $row) {
                    $officialAssessment = OfficialAssessment::where("contract_id", "=", $row->contract_id)->first();
                    $rows[] = [
                        $i + 1, $officialAssessment->certificate_code, Carbon::parse($row->certificate_date)->format('d/m/Y'), array_key_exists($row->contract->tdv_migrate, $users) ? $users[$row->contract->tdv_migrate] : "", array_key_exists($row->contract->legal_representative, $users) ? $users[$row->contract->legal_representative] : "", $row->contract->property,
                        $row->contract->purpose, $row->contract->appraisal_date, join(', ', $officialAssessment->assessment_type),
                        number_format($officialAssessment->official_value), array_key_exists($officialAssessment->performer, $users) ? $users[$officialAssessment->performer] : "", $row->contract->branch->branch_name
                    ];
                }
            } else {
                $headers = ['STT', 'Số hợp đồng', 'Hồ sơ thẩm định giá', 'Tình trạng', 'Chi nhánh'];
                if (session('result')['branch_id']) {
                    $query = ContractAcceptance::where("branch_id", session('result')['branch_id'])->where("status", 35);
                } else {
                    $query = ContractAcceptance::query();
                }
                if (!is_null(($data["formated_from_date"]))) {
                    $query->where('created_at', '>=', $data["formated_from_date"]);
                }
                if (!is_null(($data["formated_to_date"]))) {
                    $query->where('created_at', '<=', $data["formated_to_date"]);
                }
                $result = $query->get();
                $rows = [];
                foreach ($result as $i => $row) {
                    $rows[] = [$i + 1, $row->contract->code, $row->id, Status::find($row->status)->done == 1 ? "Đã hoàn thành" : "Đang xử lý", $row->contract->branch->branch_name];
                }
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
