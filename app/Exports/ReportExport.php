<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ReportExport implements FromArray
{
    protected $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function array(): array
    {
        return $this->report;
    }
}