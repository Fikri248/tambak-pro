<?php

namespace App\Exports\Reports;

use Closure;
use Illuminate\Database\Query\Builder;

class ReportExportDefinition
{
    /**
     * @param  array<int, string>  $headers
     * @param  Closure(object, string): array<int, mixed>  $mapper
     * @param  array<string, string>  $columnFormats
     * @param  array<int, string>  $metadata
     */
    public function __construct(
        public readonly string $title,
        public readonly string $worksheet,
        public readonly string $filename,
        public readonly array $headers,
        public readonly Builder $query,
        private readonly Closure $mapper,
        public readonly array $columnFormats = [],
        public readonly array $metadata = [],
    ) {}

    /** @return array<int, mixed> */
    public function map(object $row, string $format): array
    {
        return array_map(
            fn (mixed $value): mixed => is_string($value) ? $this->sanitizeText($value) : $value,
            ($this->mapper)($row, $format),
        );
    }

    private function sanitizeText(string $value): string
    {
        if ($value !== '' && $value !== '-' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'{$value}";
        }

        return $value;
    }
}
