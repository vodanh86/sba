<?php

Namespace App\Admin\Extensions;

use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Encore\Admin\Grid\Exporters\AbstractExporter;

class ExcelExporter extends AbstractExporter
{
    protected $fileName;

    protected $data;

    public function __construct($inputFileName, $inputData) {
        $this->fileName = $inputFileName;
        $this->data = $inputData;
    }

    public function export()
    {
        $export = new ReportExport($this->data);
        Excel::download($export, $this->fileName)->send();
        exit;
    }
}
