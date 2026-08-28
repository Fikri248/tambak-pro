<x-layouts.app title="Laporan Operasional">
    <div class="space-y-6">
        <x-page-header title="Laporan Operasional" description="Pantau stok, aktivitas budidaya, penggunaan pakan, dan data operasional tambak." />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan laporan operasional">
            @foreach ($summaryCards as $card)
                <x-kpi-card :label="$card['label']" :value="$card['value']" :suffix="$card['suffix']" :icon="$card['icon']" />
            @endforeach
        </section>

        <section aria-labelledby="report-list-title">
            <div class="mb-3">
                <h2 id="report-list-title" class="text-base font-semibold text-neutral-950">Daftar Laporan</h2>
                <p class="mt-1 text-xs text-neutral-500">Pilih laporan untuk melihat rincian dan filter operasional.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($reportCards as $report)
                    <a href="{{ $report['url'] }}" class="group flex min-h-44 flex-col rounded-xl border border-neutral-200 bg-white p-5 transition-colors hover:border-neutral-300 hover:bg-neutral-50/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">
                        <span class="flex size-9 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-600">
                            <x-icon :name="$report['icon']" class="size-4.5" />
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-neutral-950">{{ $report['title'] }}</h3>
                        <p class="mt-1 text-xs leading-5 text-neutral-500">{{ $report['description'] }}</p>
                        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
                            <span class="text-sm font-medium tabular-nums text-neutral-800">{{ $report['metric'] }}</span>
                            <span class="text-xs font-medium text-neutral-600 group-hover:text-neutral-950">Lihat Laporan →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
