<?php

namespace App\Admin\Controllers;

use App\Http\Models\AdminUser;
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
            return number_format($money, 2, ',', ' ');
        };
        if ($contract->customer_type == 1) {
            $name = 'SBA-HDCN-' . $contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-HDCN.docx");
            $document->setValue('code',  $contract->code);
            $document->setValue('personal_name',  $contract->personal_name);
            $document->setValue('address',  $contract->personal_address);
            $document->setValue('id_number',  $contract->id_number);
            $document->setValue('issue_place',  $contract->issue_place);
            $document->setValue('purpose',  $contract->purpose);
            $document->setValue('property',  $contract->property);
            $document->setValue('total_fee',  $moneyFormatter($contract->total_fee));
            $document->setValue('total_fee_words',  Utils::numberToWords($contract->total_fee));
            $document->setValue('appraisal_date',  $contract->appraisal_date);
            $document->setValue('today',  $today);
        } else {
            $name = 'SBA-HDDN-' . $contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-HDDN.docx");
            $document->setValue('code',  $contract->code);
            $document->setValue('business_name',  $contract->business_name);
            $document->setValue('address',  $contract->business_address);
            $document->setValue('taxNumber',  $contract->tax_number);
            $document->setValue('purpose',  $contract->purpose);
            $document->setValue('property',  $contract->property);
            $document->setValue('total_fee',  $moneyFormatter($contract->total_fee));
            $document->setValue('total_fee_words',  Utils::numberToWords($contract->total_fee));
            $document->setValue('representative',  $contract->representative);
            $document->setValue('position',  $contract->position);
            $document->setValue('appraisal_date',  $contract->appraisal_date);
            $document->setValue('today',  $today);
        }

        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");
    }


    public function createInvitationLetter(Request $request)
    {
        $moneyFormatter = function ($money) {
            return number_format($money, 2, ',', ' ');
        };
        $id = $request->input('id');
        $today = Utils::generateDate();
        $invitationLetter = InvitationLetter::find($id);
        $name = 'SBA' . '-' . 'TCG' . '-' . $invitationLetter->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-TCG.docx");
        $document->setValue('code',  $invitationLetter->code);
        $document->setValue('today',  $today);
        $document->setValue('business_name',  $invitationLetter->customer_name);
        $document->setValue('property_type',  $invitationLetter->property_type);
        $document->setValue('purpose',  $invitationLetter->purpose);
        $document->setValue('appraisal_date',  $invitationLetter->appraisal_date);
        $document->setValue('total_fee',  $moneyFormatter($invitationLetter->total_fee));
        $document->setValue('total_fee_words',  Utils::numberToWords($invitationLetter->total_fee));
        $document->setValue('working_days',  $invitationLetter->working_days);
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");;
    }

    public function createOfficialAssessment(Request $request)
    {
        $moneyFormatter = function ($money) {
            return number_format($money, 2, ',', ' ');
        };
        $convertIdToNameUser = function ($userId) {
            $adminUser = AdminUser::find($userId);
            return $adminUser ? mb_strtoupper($adminUser->name, 'UTF-8') : '';
        };
        $id = $request->input('id');
        $today = Utils::generateDate();
        $officialAssessment = OfficialAssessment::find($id);
        $name = 'SBA' . '-' . 'CT' . '-' . $officialAssessment->contract->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-CT.docx");
        $document->setValue('code',  $officialAssessment->contract->code);
        $document->setValue('today',  $today);
        $document->setValue('personal_name',  $officialAssessment->contract->personal_name);
        $document->setValue('address',  $officialAssessment->contract->personal_address);
        $document->setValue('id_number',  $officialAssessment->contract->id_number);
        $document->setValue('issue_place',  $officialAssessment->contract->issue_place);
        $document->setValue('issue_date',  $officialAssessment->contract->issue_date);
        $document->setValue('property',  $officialAssessment->contract->property);
        $document->setValue('appraisal_date',  $officialAssessment->contract->appraisal_date);
        $document->setValue('purpose',  $officialAssessment->contract->purpose);
        $document->setValue('official_value',  $moneyFormatter($officialAssessment->official_value));
        $document->setValue('official_value_words',  Utils::numberToWords($officialAssessment->official_value));
        $document->setValue('supervisor',  $convertIdToNameUser($officialAssessment->contract->tdv_migrate));  //Tham dinh vien
        $document->setValue('legal_representative',  $convertIdToNameUser($officialAssessment->contract->legal_representative));
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");;
    }

    public function createContractAcceptance(Request $request)
    {
        $moneyFormatter = function ($money) {
            return number_format($money, 2, ',', ' ');
        };
        $id = $request->input('id');
        $today = Utils::generateDate();
        $contractAcceptance = ContractAcceptance::find($id);
        $taxFee = $contractAcceptance->total_fee / 100 * 8;
        if ($contractAcceptance->contract->customer_type == 2) {
            $name = 'SBA' . '-' . 'BBNTDN' . '-' . $contractAcceptance->contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-BBNT.docx");
            $document->setValue('code',  $contractAcceptance->contract->code);
            $document->setValue('today',  $today);
            $document->setValue('appraisal_date',  $contractAcceptance->contract->created_date);
            $document->setValue('business_name',  $contractAcceptance->contract->business_name);
            $document->setValue('address',  $contractAcceptance->contract->business_address);
            $document->setValue('representative',  $contractAcceptance->contract->representative);
            $document->setValue('position',  $contractAcceptance->contract->position);
            $document->setValue('tax_number',  $contractAcceptance->tax_number);
            $document->setValue('total_fee',  $moneyFormatter($contractAcceptance->total_fee));
            $document->setValue('tax_fee',  $moneyFormatter($taxFee));
            $document->setValue('advance_fee',  $moneyFormatter($contractAcceptance->advance_fee));
            $document->setValue('official_fee',  $moneyFormatter($contractAcceptance->official_fee));
            $document->setValue('official_fee_words',  Utils::numberToWords($contractAcceptance->official_fee));
        } else {
            $name = 'SBA' . '-' . 'BBNTCN' . '-' . $contractAcceptance->contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . "/template/SBA-BBNTCN.docx");
            $document->setValue('code',  $contractAcceptance->contract->code);
            $document->setValue('today',  $today);
            $document->setValue('property',  $contractAcceptance->contract->property);
            $document->setValue('personal_name',  $contractAcceptance->contract->personal_name);
            $document->setValue('personal_address',  $contractAcceptance->contract->personal_address);
            $document->setValue('id_number',  $contractAcceptance->contract->id_number);
            $document->setValue('sale',  $contractAcceptance->contract->sale);
        }
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");;
    }
}
