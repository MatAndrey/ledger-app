<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Layout\Grid;
use App\Models\Account;
use App\Models\Transaction;

#[\MoonShine\MenuManager\Attributes\SkipMenu]
class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
	{
		return [
            Grid::make([
                ValueMetric::make('Всего счетов')
                    ->value(Account::count())
                    ->columnSpan(6),
                ValueMetric::make('Всего транзакций')
                    ->value(Transaction::count())
                    ->columnSpan(6)
            ])            
        ];
	}
}
