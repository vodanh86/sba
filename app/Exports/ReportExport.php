<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ReportExport implements FromArray
{
    protected $report;
    protected $headings;

    public function __construct(array $report, array $headings)
    {
        $this->report = $report;
        $this->headings = $headings;
    }

    public function array(): array
    {
        return $this->report;
    }
    public function headings(): array
    {
        return $this->headings;
    }
}