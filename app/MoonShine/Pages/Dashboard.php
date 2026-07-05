<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Link;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

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
                Column::make([
                    Box::make([
                        Link::make('/docs/api', 'Документация API')
                    ]),
                ])->columnSpan(6),

                Column::make([
                    Box::make([
                        Link::make('/admin/page/trial-balance', 'Оборотно-сальдовая ведомость')
                    ]),
                ])->columnSpan(6),
                
                ValueMetric::make('Всего счетов')
                    ->value(Account::count())
                    ->columnSpan(4),
                ValueMetric::make('Всего транзакций')
                    ->value(Transaction::count())
                    ->columnSpan(4),
                ValueMetric::make('Всего пользователей')
                    ->value(User::count())
                    ->columnSpan(4),
            ])
            
        ];
	}
}
