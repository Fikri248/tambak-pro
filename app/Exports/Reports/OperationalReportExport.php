<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationalReportExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly ReportExportDefinition $definition) {}

    public function query()
    {
        return clone $this->definition->query;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return $this->definition->headers;
    }

    /** @return array<int, mixed> */
    public function map($row): array
    {
        return $this->definition->map($row, 'xlsx');
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function title(): string
    {
        return mb_substr($this->definition->worksheet, 0, 31);
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return $this->definition->columnFormats;
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            5 => ['font' => ['bold' => true]],
        ];
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->definition->headers));
                $sheet->setCellValueExplicit('A1', $this->definition->title, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('A2', $this->definition->metadata[0] ?? '', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('A3', $this->definition->metadata[1] ?? '', DataType::TYPE_STRING);
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells("A3:{$lastColumn}3");
                $sheet->freezePane('A6');
                $sheet->setAutoFilter("A5:{$lastColumn}5");
            },
        ];
    }
}
