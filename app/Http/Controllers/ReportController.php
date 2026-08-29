<?php

namespace App\Http\Controllers;

use App\Exports\Reports\OperationalReportExport;
use App\Http\Requests\ReportFilterRequest;
use App\Services\Reports\OperationalReportService;
use App\Services\Reports\ReportCsvExporter;
use App\Support\PageSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly OperationalReportService $reports,
        private readonly ReportCsvExporter $csvExporter,
    ) {}

    public function index(): View
    {
        return view('reports.index', $this->reports->hub());
    }

    public function stock(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->stock($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function stocking(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->stocking($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function movements(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->movements($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function adjustments(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->adjustments($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function feeding(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->feeding($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function vendors(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->vendors($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function commodities(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->commodities($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function locations(ReportFilterRequest $request): View
    {
        return $this->reportView($this->reports->locations($this->filters($request), perPage: PageSize::resolve($request)), $request);
    }

    public function printReport(ReportFilterRequest $request, string $report): View
    {
        return view('reports.print.layout', $this->documentData($report, $request) + ['isPdf' => false]);
    }

    public function pdf(ReportFilterRequest $request, string $report): Response
    {
        $data = $this->documentData($report, $request);

        return Pdf::loadView('reports.print.layout', $data + ['isPdf' => true])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultMediaType' => 'print',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
            ])
            ->download($data['filename'].'.pdf');
    }

    public function exportStock(ReportFilterRequest $request): Response
    {
        return $this->download('stock', $request);
    }

    public function exportStocking(ReportFilterRequest $request): Response
    {
        return $this->download('stocking', $request);
    }

    public function exportMovements(ReportFilterRequest $request): Response
    {
        return $this->download('movements', $request);
    }

    public function exportAdjustments(ReportFilterRequest $request): Response
    {
        return $this->download('adjustments', $request);
    }

    public function exportFeeding(ReportFilterRequest $request): Response
    {
        return $this->download('feeding', $request);
    }

    public function exportVendors(ReportFilterRequest $request): Response
    {
        return $this->download('vendors', $request);
    }

    public function exportCommodities(ReportFilterRequest $request): Response
    {
        return $this->download('commodities', $request);
    }

    public function exportLocations(ReportFilterRequest $request): Response
    {
        return $this->download('locations', $request);
    }

    /** @return array<string, mixed> */
    private function filters(ReportFilterRequest $request): array
    {
        return $this->reports->filters($request->validated());
    }

    /** @param array<string, mixed> $data */
    private function reportView(array $data, ReportFilterRequest $request): View
    {
        $routeName = (string) $request->route()?->getName();

        return view('reports.show', $data + [
            'filters' => $this->filters($request),
            'exportRoute' => $routeName.'.export',
            'printRoute' => $routeName.'.print',
            'pdfRoute' => $routeName.'.pdf',
        ]);
    }

    /** @return array<string, mixed> */
    private function documentData(string $report, ReportFilterRequest $request): array
    {
        $filters = $this->filters($request);
        $data = $this->reports->document($report, $filters);

        return $data + [
            'reportKey' => $report,
            'backUrl' => route('reports.'.$report, array_filter(
                $filters,
                fn (mixed $value): bool => $value !== null && $value !== '',
            )),
        ];
    }

    private function download(string $report, ReportFilterRequest $request): Response
    {
        $definition = $this->reports->export($report, $this->filters($request));
        $format = $request->validated('format') ?? 'csv';

        if ($format === 'csv') {
            return $this->csvExporter->download($definition);
        }

        return Excel::download(
            new OperationalReportExport($definition),
            $definition->filename.'.xlsx',
            ExcelWriter::XLSX,
        );
    }
}
