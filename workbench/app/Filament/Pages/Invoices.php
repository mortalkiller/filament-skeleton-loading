<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Workbench\App\Support\Latency;

/**
 * A table that loads with the page and then updates: changing page, searching, filtering, sorting
 * and switching tabs. With `showSkeletonsOnTableUpdate()` each update shows skeleton rows.
 */
final class Invoices extends Page implements HasTable
{
    use InteractsWithTable;

    public const STATUSES = ['paid' => 'Paid', 'pending' => 'Pending', 'overdue' => 'Overdue'];

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $slug = 'invoices';

    protected string $view = 'workbench::invoices';

    /** Like the tabs of a resource list: a status, or `all`. */
    public string $activeTab = 'all';

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, ?array $filters, int|string $page, int|string $recordsPerPage, ?string $sortColumn, ?string $sortDirection): LengthAwarePaginator {
                Latency::wait();

                $status = $filters['status']['value'] ?? null;
                $tab = $this->activeTab === 'all' ? null : $this->activeTab;

                $records = collect(self::invoices())
                    ->when($tab, fn ($records) => $records->where('status', self::STATUSES[$tab]))
                    ->when($status, fn ($records) => $records->where('status', self::STATUSES[$status] ?? null))
                    ->when(filled($search), fn ($records) => $records->filter(
                        fn (array $invoice): bool => str_contains(strtolower($invoice['reference'].' '.$invoice['customer']), strtolower((string) $search)),
                    ))
                    ->when($sortColumn, fn ($records) => $records->sortBy($sortColumn, descending: $sortDirection === 'desc'))
                    ->values();

                $perPage = (int) $recordsPerPage ?: 10;

                return new LengthAwarePaginator($records->forPage((int) $page, $perPage), $records->count(), $perPage, (int) $page);
            })
            ->columns([
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('customer')->searchable()->sortable(),
                TextColumn::make('status'),
                TextColumn::make('total')->money('EUR')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES),
            ])
            ->paginated([10]);
    }

    /** @return list<array{id: int, reference: string, customer: string, status: string, total: float}> */
    private static function invoices(): array
    {
        $statuses = array_values(self::STATUSES);

        return array_map(fn (int $number): array => [
            'id' => $number,
            'reference' => sprintf('INV-%04d', 2000 + $number),
            'customer' => 'Customer '.chr(64 + (($number * 7) % 26) + 1),
            'status' => $statuses[$number % 3],
            'total' => (($number * 37) % 400) + 15.5,
        ], range(1, 48));
    }
}
