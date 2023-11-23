<?php

namespace App\Admin\Controllers;

use App\Http\Models\AdminUser;
use App\Http\Models\DocsConfig;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Http\Models\Contract;
use App\Http\Models\InvitationLetter;
use App\Http\Models\OfficialAssessment;
use App\Http\Models\ContractAcceptance;

class WordController extends AdminController
{
    public function createContract(Request $request)
    {
        $id = $request->input('id');
        $contract = Contract::find($id);
        $today = Utils::generateDate();
        $moneyFormatter = function ($money) {
            return number_format($money);
        };

        if ($contract->customer_type == 1) {
            $name = 'SBA-HDCN-' . $contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-HDCN.docx");
            if ($contract->docs_stk) {
                $document->setValue("stk", $contract->docs_stk);
                $nameStk = DocsConfig::where("type", "Hợp đồng cá nhân")->where("branch_id", $contract->branch_id)->where("value", $contract->docs_stk)->first();
                if ($nameStk && $nameStk->value == $contract->docs_stk) {
                    $document->setValue("ten_stk", $nameStk->description);
                }
            } else {
                $document->setValue("stk", "686878988");
                $document->setValue("ten_stk", "Ngân hàng TMCP Quân Đội – Chi nhánh Hoàng Quốc Việt");
            }
            if ($contract->docs_representative && $contract->docs_position) {
                $document->setValue("dai_dien", $contract->docs_representative);
                $document->setValue("chuc_vu", $contract->docs_position);
            } else if ($contract->branch_id == 3) {
                $document->setValue("dai_dien", "Phạm Vũ Minh Phúc");
                $document->setValue("chuc_vu", "Tổng Giám đốc");
            } else {
                $document->setValue("dai_dien", "Lê Minh Tiến");
                $document->setValue("chuc_vu", "Phó Tổng Giám đốc");
            }

            $paymentLeftValue = ($contract->total_fee) - ($contract->advance_fee);
            if ($contract->total_fee > 0) {
                $document->setValue('payment_left_words', Utils::numberToWords($paymentLeftValue));
                $document->setValue('advance_fee_words', Utils::numberToWords($contract->advance_fee));
            } else {
                $document->setValue('payment_left_words', "Không đồng");
                $document->setValue('advance_fee_words', "Không đồng");
            }

            $document->setValue('payment_left', ($moneyFormatter($paymentLeftValue)));
            $document->setValue('advance_fee', $moneyFormatter(($contract->advance_fee)));
            $document->setValue('issue_date', $contract->issue_date);
            if ($contract->docs_authorization == "") {
                $document->setValue("uy_quyen", "");
            } else {
                $document->setValue("uy_quyen", $contract->docs_authorization);
            }
            $document->setValue('code', $contract->code);
            $document->setValue('personal_name', $contract->personal_name);
            $document->setValue('address', $contract->personal_address);
            $document->setValue('id_number', $contract->id_number);
            $document->setValue('issue_place', $contract->issue_place);
            $document->setValue('purpose', $contract->purpose);
            $document->setValue('property', $contract->property);
            $document->setValue('total_fee', $moneyFormatter($contract->total_fee));
            $document->setValue('total_fee_words', Utils::numberToWords($contract->total_fee));
            $document->setValue('appraisal_date', $contract->appraisal_date);
            $document->setValue('today', $today);
        } else {
            $name = 'SBA-HDDN-' . $contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-HDDN.docx");
            if ($contract->docs_stk) {
                $document->setValue("stk", $contract->docs_stk);
                $nameStk = DocsConfig::where("type", "Hợp đồng doanh nghiệp")->where("branch_id", $contract->branch_id)->where("value", $contract->docs_stk)->first();
                if ($nameStk && $nameStk->value == $contract->docs_stk) {
                    $document->setValue("ten_stk", $nameStk->description);
                }
            } else {
                $document->setValue("stk", "686878988");
                $document->setValue("ten_stk", "Ngân hàng TMCP Quân Đội – Chi nhánh Hoàng Quốc Việt");
            }
            if ($contract->docs_representative && $contract->docs_position) {
                $document->setValue("dai_dien", $contract->docs_representative);
                $document->setValue("chuc_vu", $contract->docs_position);
            } else if ($contract->branch_id == 3) {
                $document->setValue("dai_dien", "Phạm Vũ Minh Phúc");
                $document->setValue("chuc_vu", "Tổng Giám đốc");
            } else {
                $document->setValue("dai_dien", "Lê Minh Tiến");
                $document->setValue("chuc_vu", "Phó Tổng Giám đốc");
            }

            $paymentLeftValue = ($contract->total_fee) - ($contract->advance_fee);
            if ($contract->total_fee > 0) {
                $document->setValue('payment_left_words', Utils::numberToWords($paymentLeftValue));
                $document->setValue('advance_fee_words', Utils::numberToWords($contract->advance_fee));
            } else {
                $document->setValue('payment_left_words', "Không");
                $document->setValue('advance_fee_words', "Không");
            }
            $document->setValue('payment_left', ($moneyFormatter(($paymentLeftValue))));
            $document->setValue('advance_fee', $moneyFormatter($contract->advance_fee));
            $document->setValue('issue_date', $contract->issue_date);
            if ($contract->docs_authorization == "") {
                $document->setValue("uy_quyen", "");
            } else {
                $document->setValue("uy_quyen", $contract->docs_authorization);
            }
            $document->setValue('code', $contract->code);
            $document->setValue('business_name', $contract->business_name);
            $document->setValue('address', $contract->business_address);
            $document->setValue('taxNumber', $contract->tax_number);
            $document->setValue('purpose', $contract->purpose);
            $document->setValue('property', $contract->property);
            $document->setValue('total_fee', $moneyFormatter($contract->total_fee));
            $document->setValue('total_fee_words', Utils::numberToWords($contract->total_fee));
            $document->setValue('representative', $contract->representative);
            $document->setValue('position', $contract->position);
            $document->setValue('appraisal_date', $contract->appraisal_date);
            $document->setValue('today', $today);
        }

        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");
    }


    public function createInvitationLetter(Request $request)
    {
        $moneyFormatter = function ($money) {
            return number_format($money);
        };
        $id = $request->input('id');
        $today = Utils::generateDate();
        $invitationLetter = InvitationLetter::find($id);
        $name = 'SBA' . '-' . 'TCG' . '-' . $invitationLetter->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-TCG.docx");
        $document->setValue('code', $invitationLetter->code);
        $document->setValue('today', $today);
        $document->setValue('business_name', $invitationLetter->customer_name);
        $document->setValue('property_type', $invitationLetter->property_type);
        $document->setValue('purpose', $invitationLetter->purpose);
        $document->setValue('appraisal_date', $invitationLetter->appraisal_date);
        $document->setValue('total_fee', $moneyFormatter($invitationLetter->total_fee));
        $document->setValue('total_fee_words', Utils::numberToWords($invitationLetter->total_fee));
        $document->setValue('working_days', $invitationLetter->working_days);
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");
        ;
    }

    public function createOfficialAssessment(Request $request)
    {
        $dateFormatter = function ($date) {
            $timestamp = strtotime($date);
            $formattedDate = date('\n\g\à\y d \t\h\á\n\g m \n\ă\m Y', $timestamp);
            return $formattedDate;
        };
        $moneyFormatter = function ($money) {
            return number_format($money);
        };
        $convertIdToNameUser = function ($userId) {
            $adminUser = AdminUser::find($userId);
            return $adminUser ? mb_strtoupper($adminUser->name, 'UTF-8') : '';
        };
        $convertIdToNameUserDefault = function ($userId) {
            $adminUser = AdminUser::find($userId);
            return $adminUser ? $adminUser->name : '';
        };
        $id = $request->input('id');
        $today = Utils::generateDate();
        $officialAssessment = OfficialAssessment::find($id);
        $name = 'SBA' . '-' . 'CT' . '-' . $officialAssessment->contract->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-CT.docx");
        $docsConfig = DocsConfig::where("type", "Chứng thư")->where("branch_id", $officialAssessment->contract->branch_id)->get();
        if ($docsConfig) {
            $chuc_vu = "";
            $key_tdv = "";
            $key_ddpl = "";
            foreach ($docsConfig as $config) {
                if ($config->key == "chuc_vu") {
                    $chuc_vu = $config->value;
                }

                if ($config->key == "key_tđv" && $config->value == $convertIdToNameUserDefault($officialAssessment->contract->tdv_migrate)) {
                    $key_tdv = $config->description;
                }

                if ($config->key == "key_đdpl" && $config->value == $convertIdToNameUserDefault($officialAssessment->contract->legal_representative)) {
                    $key_ddpl = $config->description;
                }
            }
            $document->setValue("chuc_vu", $chuc_vu);
            $document->setValue("key_tđv", $key_tdv);
            $document->setValue("key_đdpl", $key_ddpl);
        }

        if ($officialAssessment->contract->branch_id == 4) {
            $document->setValue('branch', "Hồ Chí Minh");
        } else if ($officialAssessment->contract->branch_id == 3) {
            $document->setValue('branch', "Hà Nội");
        } else if ($officialAssessment->contract->branch_id == 5) {
            $document->setValue('branch', "Bắc Ninh");
        }
        $document->setValue('code', $officialAssessment->contract->code);
        $document->setValue('today', $today);
        $document->setValue('created_date', $dateFormatter($officialAssessment->contract->created_date));
        $document->setValue('certificate_date', $dateFormatter($officialAssessment->certificate_date));
        if ($officialAssessment->contract->personal_name == "") {
            $document->setValue('personal_name', $officialAssessment->contract->business_name);
        } else {
            $document->setValue('personal_name', $officialAssessment->contract->personal_name);
        }
        if ($officialAssessment->contract->personal_address == "") {
            $document->setValue('address', $officialAssessment->contract->business_address);
        } else {
            $document->setValue('address', $officialAssessment->contract->personal_address);
        }
        $document->setValue('id_number', $officialAssessment->contract->id_number);
        $document->setValue('issue_place', $officialAssessment->contract->issue_place);
        $document->setValue('issue_date', $officialAssessment->contract->issue_date);
        $document->setValue('property', $officialAssessment->contract->property);
        $document->setValue('appraisal_date', $officialAssessment->contract->appraisal_date);
        $document->setValue('purpose', $officialAssessment->contract->purpose);
        $document->setValue('official_value', $moneyFormatter($officialAssessment->official_value));
        $document->setValue('official_value_words', Utils::numberToWords($officialAssessment->official_value));
        $document->setValue('supervisor', $convertIdToNameUser($officialAssessment->contract->tdv_migrate));  //Tham dinh vien
        $document->setValue('legal_representative', $convertIdToNameUser($officialAssessment->contract->legal_representative));
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");
        ;
    }

    public function createContractAcceptance(Request $request)
    {
        $dateFormatter = function ($date) {
            $timestamp = strtotime($date);
            $formattedDate = date('\n\g\à\y d \t\h\á\n\g m \n\ă\m Y', $timestamp);
            return $formattedDate;
        };
        $moneyFormatter = function ($money) {
            return number_format($money);
        };
        $id = $request->input('id');
        $today = Utils::generateDate();
        $contractAcceptance = ContractAcceptance::find($id);
        if ($contractAcceptance->contract->customer_type == 2) {
            $name = 'SBA' . '-' . 'BBNTDN' . '-' . $contractAcceptance->contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-BBNT.docx");
            $docsConfig = DocsConfig::where("type", "Nghiệm thu hợp đồng doanh nghiệp")->where("branch_id", $contractAcceptance->contract->branch_id)->get();
            if ($docsConfig) {
                foreach ($docsConfig as $config) {
                    if ($config->key == "thue_VAT") {
                        $totalWithoutTaxFee = $contractAcceptance->total_fee / 1.08;
                        $taxFee = $totalWithoutTaxFee / 100 * $config->value;
                        $document->setValue("thue_VAT", $config->value);
                    }
                }
            }
            if ($contractAcceptance->contract->docs_stk) {
                $document->setValue("stk", $contractAcceptance->contract->docs_stk);
                $nameStk = DocsConfig::where("type", "Hợp đồng cá nhân")->where("branch_id", $contractAcceptance->contract->branch_id)->where("stk", $contractAcceptance->contract->docs_stk)->first()->description;
                if ($nameStk) {
                    $document->setValue("ten_stk", $nameStk);
                }
            } else {
                $document->setValue("stk", "686878988");
                $document->setValue("ten_stk", "Ngân hàng TMCP Quân Đội – Chi nhánh Hoàng Quốc Việt");
            }
            if ($contractAcceptance->contract->docs_representative && $contractAcceptance->contract->docs_position) {
                $document->setValue("dai_dien", $contractAcceptance->contract->docs_representative);
                $document->setValue("chuc_vu", $contractAcceptance->contract->docs_position);
            } else if ($contractAcceptance->contract->branch_id == 3) {
                $document->setValue("dai_dien", "Phạm Vũ Minh Phúc");
                $document->setValue("chuc_vu", "Tổng Giám đốc");
            } else {
                $document->setValue("dai_dien", "Lê Minh Tiến");
                $document->setValue("chuc_vu", "Phó Tổng Giám đốc");
            }
            $document->setValue("uy_quyen", $contractAcceptance->contract->docs_authorization && "");
            $document->setValue('code', $contractAcceptance->contract->code);
            $document->setValue('today', $today);
            $document->setValue('created_date', $dateFormatter($contractAcceptance->contract->created_date));
            $document->setValue('appraisal_date', $contractAcceptance->contract->created_date);
            $document->setValue('business_name', $contractAcceptance->contract->business_name);
            $document->setValue('address', $contractAcceptance->contract->business_address);
            $document->setValue('representative', $contractAcceptance->contract->representative);
            $document->setValue('position', $contractAcceptance->contract->position);
            $document->setValue('tax_number', $contractAcceptance->tax_number);
            $document->setValue('total_fee', $moneyFormatter($contractAcceptance->total_fee));
            $document->setValue('total_none_fee', $moneyFormatter($totalWithoutTaxFee));
            $document->setValue('tax_fee', $moneyFormatter($taxFee));
            $document->setValue('advance_fee', $moneyFormatter($contractAcceptance->advance_fee));
            $document->setValue('official_fee', $moneyFormatter($contractAcceptance->official_fee));
            $document->setValue('official_fee_words', Utils::numberToWords($contractAcceptance->official_fee));
        } else {
            $name = 'SBA' . '-' . 'BBNTCN' . '-' . $contractAcceptance->contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-BBNTCN.docx");
            $document->setValue('created_date', $dateFormatter($contractAcceptance->contract->created_date));
            $document->setValue('code', $contractAcceptance->contract->code);
            $document->setValue('today', $today);
            $document->setValue('property', $contractAcceptance->contract->property);
            $document->setValue('personal_name', $contractAcceptance->contract->personal_name);
            $document->setValue('personal_address', $contractAcceptance->contract->personal_address);
            $document->setValue('id_number', $contractAcceptance->contract->id_number);
            $document->setValue('sale', $contractAcceptance->contract->sale);
        }
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");
    }
}
