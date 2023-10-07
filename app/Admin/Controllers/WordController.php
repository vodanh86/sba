<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Http\Models\Contract;

class WordController extends AdminController
{
    public function createContract(Request $request)
    {
        $id = $request->input('id');
        $contract = Contract::find($id);
        $document = new \PhpOffice\PhpWord\TemplateProcessor(public_path()."/template/SBA-HDDN.docx");
        $document->setValue('address',  $contract->business_address);
        $document->setValue('taxNumber',  $contract->tax_number);
        $document->saveAs(storage_path()."/output.docx");

        return response()->file(storage_path()."/output.docx");
    }
}