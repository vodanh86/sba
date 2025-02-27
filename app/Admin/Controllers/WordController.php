<?php

namespace App\Admin\Controllers;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

use App\Http\Models\AdminUser;
use App\Http\Models\DocsConfig;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Http\Models\Contract;
use App\Http\Models\InvitationLetter;
use App\Http\Models\OfficialAssessment;
use App\Http\Models\ContractAcceptance;
use Illuminate\Support\Facades\DB;

class WordController extends AdminController
{
    public function createContract(Request $request)
    {
        $dateFormatter = function ($date) {
            $timestamp = strtotime($date);
            $formattedDate = date('\n\g\à\y d \t\h\á\n\g m \n\ă\m Y', $timestamp);
            return $formattedDate;
        };
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
            $document->setValue('created_date', $dateFormatter($contract->created_date));
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

            $document->setValue('created_date', $dateFormatter($contract->created_date));
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

        // Kiểm tra nếu không tìm thấy đánh giá chính thức
        if (!$officialAssessment) {
            throw new \Exception("Không tìm thấy đánh giá chính thức với ID: $id");
        }

        // Chỉ tạo mới certificate_code nếu chưa có
        if (empty($officialAssessment->certificate_code)) {
            // Lock hàng trong DB để tránh race condition
            $lockingRecord = DB::table('f_locking')->where('key', 'chungthu')->lockForUpdate()->first();

            if (!$lockingRecord) {
                throw new \Exception("Không tìm thấy bản ghi 'chungthu' trong bảng f_locking.");
            }

            $currentYear = now()->year;

            // Nếu năm đã thay đổi, reset giá trị về 10000
            if ($lockingRecord->year != $currentYear) {
                $newValue = 10000;
                DB::table('f_locking')->where('id', $lockingRecord->id)->update([
                    'value' => $newValue,
                    'year' => $currentYear,
                ]);
            } else {
                $newValue = $lockingRecord->value + 1;
                DB::table('f_locking')->where('id', $lockingRecord->id)->update([
                    'value' => $newValue,
                ]);
            }

            // Định dạng số thứ tự
            $formattedValue = sprintf('%05d', $newValue - 10000);

            // Xây dựng mã chứng chỉ
            $fixedPrefix = '316';
            $year = $currentYear; // Dùng giá trị mới nhất của `year`
            $contractCodeParts = explode('.', $officialAssessment->contract->code);
            $contractSuffix = end($contractCodeParts);

            $generatedCertificateCode = "{$fixedPrefix}/{$year}/{$formattedValue}.{$contractSuffix}";

            // Cập nhật certificate_code
            $officialAssessment->certificate_code = $generatedCertificateCode;
        }

        // Tăng số lần in
        $officialAssessment->num_of_prints += 1;
        $officialAssessment->save();


        $numPrintsFormatted = ($officialAssessment->num_of_prints < 10) ? sprintf('%02d', $officialAssessment->num_of_prints) : $officialAssessment->num_of_prints;
        $name = 'SBA' . '-' . 'CT' . '-' . $officialAssessment->contract->code . '-' . $numPrintsFormatted;

        $templatePath = "/template/SBA-CT-NEW.docx";
        if ($officialAssessment->contract->branch_id == 3) {
            $templatePath = "/template/SBA-CT-HN.docx";
        } elseif ($officialAssessment->contract->branch_id == 4) {
            $templatePath = "/template/SBA-CT-HCM.docx";
        }

        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . $templatePath);
        $writer = new PngWriter();

        $contractCode = $officialAssessment->contract->code;
        $qrCodesDir = public_path('storage/qr_codes');
        if (!file_exists($qrCodesDir)) {
            mkdir($qrCodesDir, 0777, true);
        }

        $existingQrRecord = DB::table('qr_codes')->where('contract_code', $contractCode)->first();

        if (!$existingQrRecord) {
            $qrLink = env('APP_URL') . '/qr/' . base64_encode($contractCode);

            $qrRecordId = DB::table('qr_codes')->insertGetId([
                'contract_code' => $contractCode,
                'qr_code' => $qrLink,
                'pin_code' => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
                'expiration_date' => now()->addDays(30),
            ]);

            $qrImagePath = public_path("storage/qr_codes/qr_code_" . $contractCode . '.png');
            $qrCode = new QrCode($qrLink);

            $writer = new PngWriter();
            $writer->write($qrCode)->saveToFile($qrImagePath);
            $qrPinCode = DB::table('qr_codes')->where('id', $qrRecordId)->value('pin_code');
        } else {
            $qrImagePath = public_path("storage/qr_codes/qr_code_" . $contractCode . '.png');
            $qrPinCode = $existingQrRecord->pin_code;
        }

        if (!file_exists($qrImagePath)) {
            throw new \Exception("QR code image not found: $qrImagePath");
        }

        $qrImageLink = $qrImagePath;

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

        $suffix = explode('.', $contractCode)[1] ?? '';

        $document->setImageValue('qr_link', $qrImageLink);
        $document->setValue('branch_code', $suffix);
        $document->setValue('pin_code', $qrPinCode);
        $document->setValue('original_number', $officialAssessment->certificate_code);

        $document->setValue('code', $officialAssessment->contract->code);
        $document->setValue('today', $today);
        $document->setValue('created_date', $dateFormatter($officialAssessment->contract->created_date));
        $document->setValue('certificate_date', $dateFormatter($officialAssessment->certificate_date));
        if ($officialAssessment->contract->personal_name == "") {
            $document->setValue('full_name', $officialAssessment->contract->business_name);
        } else {
            $document->setValue('full_name', $officialAssessment->contract->personal_name);
        }
        if ($officialAssessment->contract->personal_address == "") {
            $document->setValue('full_address', $officialAssessment->contract->business_address);
        } else {
            $document->setValue('full_address', $officialAssessment->contract->personal_address);
        }

        if ($officialAssessment->contract->tax_number == "") {
            $document->setValue('full_identify', $officialAssessment->contract->id_number . ' do ' . $officialAssessment->contract->issue_place . ' cấp ngày ' . $officialAssessment->contract->issue_date);
            $document->setValue('representative', '………………………………………');
        } else {
            $document->setValue('full_identify', $officialAssessment->contract->tax_number);
            $document->setValue('representative', $officialAssessment->contract->representative);
        }

        $assessmentType = is_array($officialAssessment->assessment_type)
            ? implode(', ', $officialAssessment->assessment_type)
            : $officialAssessment->assessment_type;

        $document->setValue('property', $officialAssessment->contract->property);
        $document->setValue('appraisal_date', $officialAssessment->created_date);
        $document->setValue('purpose', $officialAssessment->contract->purpose);
        $document->setValue('assessment_type', $assessmentType);
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
            $document->setValue('appraisal_date', date('d/m/Y', strtotime($contractAcceptance->contract->created_date)));
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
