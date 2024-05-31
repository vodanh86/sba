<?php

namespace App\Admin\Extensions;

use App\Exports\DataExport;
use Maatwebsite\Excel\Facades\Excel;
use Encore\Admin\Grid\Exporters\AbstractExporter;

class ExcelExporter extends AbstractExporter
{
    protected $fileName;
    protected $dataProcessor;
    protected $branchId;
    protected $headings;


    public function __construct($fileName, $dataProcessor, $branchId, array $headings)
    {
        $this->fileName = $fileName;
        $this->dataProcessor = $dataProcessor;
        $this->branchId = $branchId;
        $this->headings = $headings;
    }

    public function export()
    {
        $data = $this->processData();

        $export = new DataExport($data, $this->headings);
        Excel::download($export, $this->fileName)->send();
        exit;
    }

    protected function processData()
    {
        return call_user_func($this->dataProcessor, $this->branchId);
    }
}

