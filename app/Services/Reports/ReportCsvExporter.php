<?php

namespace App\Services\Reports;

use App\Exports\Reports\ReportExportDefinition;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvExporter
{
    public function download(ReportExportDefinition $definition): StreamedResponse
    {
        return response()->streamDownload(function () use ($definition): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new \RuntimeException('Tidak dapat membuka stream CSV.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $definition->headers);

            foreach ((clone $definition->query)->lazy(500) as $row) {
                fputcsv($stream, $definition->map($row, 'csv'));
            }

            fclose($stream);
        }, $definition->filename.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
