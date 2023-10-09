<?php

namespace App\Admin\Controllers;

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
        
        if ($contract->customer_type == 1){
            $name = 'SBA-HDCN-' . $contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-HDCN.docx");
            $document->setValue('personal_name',  $contract->personal_name);
            $document->setValue('address',  $contract->address);
            $document->setValue('id_number',  $contract->id_number);
            $document->setValue('issue_place',  $contract->issue_place);
            $document->setValue('purpose',  $contract->purpose);
            $document->setValue('total_fee',  $contract->total_fee);
            $document->setValue('appraisal_date',  $contract->appraisal_date);
        } else {
            $name = 'SBA-HDDN-' . $contract->code;
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-HDDN.docx");
            $document->setValue('address',  $contract->business_address);
            $document->setValue('taxNumber',  $contract->tax_number);
            $document->setValue('purpose',  $contract->purpose);
            $document->setValue('total_fee',  $contract->total_fee);
            $document->setValue('representative',  $contract->representative);
            $document->setValue('position',  $contract->position);
            $document->setValue('appraisal_date',  $contract->appraisal_date);
        }
    
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");
    }
    

    public function createInvitationLetter(Request $request)
    {
        $id = $request->input('id');
        $invitationLetter = InvitationLetter::find($id);
        $name = 'SBA'. '-' . 'TCG' . '-' . $invitationLetter->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-TCG.docx");
        $document->setValue('property_type',  $invitationLetter->property_type);
        $document->setValue('purpose',  $invitationLetter->purpose);
        $document->setValue('appraisal_date',  $invitationLetter->appraisal_date);
        $document->setValue('total_fee',  $invitationLetter->total_fee);
        $document->setValue('working_days',  $invitationLetter->working_days);
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");;
    }

    public function createOfficialAssessment(Request $request)
    {
        $id = $request->input('id');
        $officialAssessment = OfficialAssessment::find($id);
        $name = 'SBA'. '-' . 'CT' . '-' . $officialAssessment->contract->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-CT.docx");
        $document->setValue('customer_name',  $officialAssessment->contract->customer_name);
        $document->setValue('address',  $officialAssessment->contract->address);
        $document->setValue('id_number',  $officialAssessment->contract->id_number);
        $document->setValue('issue_place',  $officialAssessment->contract->issue_place);
        $document->setValue('issue_date',  $officialAssessment->contract->issue_date);
        $document->setValue('property',  $officialAssessment->contract->property);
        $document->setValue('appraisal_date',  $officialAssessment->contract->appraisal_date);
        $document->setValue('purpose',  $officialAssessment->contract->purpose);
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");;
    }

    public function createContractAcceptance(Request $request)
    {
        $id = $request->input('id');
        $contractAcceptance = ContractAcceptance::find($id);
        $name = 'SBA'. '-' . 'BBNT' . '-' . $contractAcceptance->contract->code;
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-BBNT.docx");
        $document->setValue('address',  $contractAcceptance->contract->address);
        $document->setValue('tax_number',  $contractAcceptance->tax_number);
        $document->setValue('representative',  $contractAcceptance->contract->representative);
        $document->setValue('position',  $contractAcceptance->contract->position);
        $document->setValue('total_fee',  $contractAcceptance->total_fee);
        $document->setValue('advance_fee',  $contractAcceptance->advance_fee);
        $document->setValue('official_fee',  $contractAcceptance->official_fee);
        $outputPath = storage_path("/$name.docx");
        $document->saveAs($outputPath);
        return response()->download($outputPath, "$name.docx");;
    }
}
