<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChartOfAccountRequest;
use App\Models\AccountDescription;
use App\Models\AccountType;
use App\Models\ChartOfAccount;
use App\Models\FinancialStatement;
use App\Support\PageSize;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;

        $accounts = ChartOfAccount::query()
            ->with(['description:id,name,status', 'accountType:id,name,status', 'financialStatement:id,name,status'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('number_code', 'like', "%{$search}%")
                        ->orWhereHas('description', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('accountType', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('financialStatement', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('number_code')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        return view('chart-of-accounts.index', [
            'accounts' => $accounts,
            'filters' => compact('search', 'status'),
            'summary' => [
                'total' => ChartOfAccount::query()->count(),
                'active' => ChartOfAccount::query()->where('status', 'ACTIVE')->count(),
                'inactive' => ChartOfAccount::query()->where('status', 'INACTIVE')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('chart-of-accounts.create', $this->formData());
    }

    public function store(ChartOfAccountRequest $request): RedirectResponse
    {
        $account = ChartOfAccount::query()->create([
            ...$request->safe()->only([
                'number_code',
                'description_id',
                'account_type_id',
                'financial_statement_id',
            ]),
            'status' => 'ACTIVE',
        ]);

        return redirect()->route('chart-of-accounts.show', $account)
            ->with('success', 'Chart of Accounts berhasil ditambahkan.');
    }

    public function show(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load(['description', 'accountType', 'financialStatement']);

        return view('chart-of-accounts.show', ['account' => $chartOfAccount]);
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load(['description', 'accountType', 'financialStatement']);

        return view('chart-of-accounts.edit', $this->formData($chartOfAccount) + ['account' => $chartOfAccount]);
    }

    public function update(ChartOfAccountRequest $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $chartOfAccount->update($request->safe()->only([
            'number_code',
            'description_id',
            'account_type_id',
            'financial_statement_id',
        ]));

        return redirect()->route('chart-of-accounts.show', $chartOfAccount)
            ->with('success', 'Chart of Accounts berhasil diperbarui.');
    }

    public function status(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $status = DB::transaction(function () use ($chartOfAccount): string {
            $account = ChartOfAccount::query()->lockForUpdate()->findOrFail($chartOfAccount->id);
            $account->update(['status' => $account->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);

            return $account->status;
        });

        return back()->with(
            'success',
            $status === 'ACTIVE'
                ? 'Chart of Accounts berhasil diaktifkan.'
                : 'Chart of Accounts berhasil dinonaktifkan.',
        );
    }

    /** @return array<string, mixed> */
    private function formData(?ChartOfAccount $account = null): array
    {
        return [
            'descriptions' => $this->availableOptions(AccountDescription::query(), $account?->description_id),
            'accountTypes' => $this->availableOptions(AccountType::query(), $account?->account_type_id),
            'financialStatements' => $this->availableOptions(FinancialStatement::query(), $account?->financial_statement_id),
        ];
    }

    private function availableOptions(Builder $query, ?int $currentId)
    {
        return $query
            ->where(function (Builder $query) use ($currentId): void {
                $query->where('status', 'ACTIVE');

                if ($currentId !== null) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'status']);
    }
}
