<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Http\Models\Contract;
use App\Http\Models\InvitationLetter;

class WordController extends AdminController
{
    public function createContract(Request $request)
    {
        $id = $request->input('id');
        $contract = Contract::find($id);
        if ($contract->customer_type == 1){
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-HDCN.docx");
        } else {
            $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-HDDN.docx");
            $document->setValue('address',  $contract->business_address);
            $document->setValue('taxNumber',  $contract->tax_number);
        }
        $document->saveAs(storage_path()."/output.docx");

        return response()->file(storage_path()."/output.docx");
    }

    public function createInvitationLetter(Request $request)
    {
        $id = $request->input('id');
        $invitationLetter = InvitationLetter::find($id);
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-TCG.docx");
        $document->saveAs(storage_path()."/output.docx");

        return response()->file(storage_path()."/output.docx");
    }
}
