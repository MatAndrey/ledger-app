<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\Transaction\TransactionResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Components\ActionButton; 
use App\Models\Account;
use Throwable;


/**
 * @extends IndexPage<TransactionResource>
 */
class TransactionIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()
                ->sortable(),
            Date::make('Дата', 'date')
                ->format('d.m.Y')
                ->sortable(),
            Text::make('Сумма', 'total_amount'),
            Text::make('Счета', 'accounts_list'),
            Text::make('Описание', 'description')
                ->sortable(),
        ];
    }

    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            DateRange::make('Дата', 'date'),
            Select::make('Счет', 'account_filter')
                ->options(Account::pluck('name', 'id')->toArray())
                ->nullable()
                ->multiple()
                ->onApply(function ($query, $value) {
                    if ($value) {
                        $query->whereHas('journalEntries', function ($q) use ($value) {
                            $q->where('account_id', $value);
                        });
                    }
                }),
        ];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }

    protected function topRightButtons(): ListOf
    {
        return parent::topRightButtons()
            ->add(
                ActionButton::make('Export CSV', '/admin/resource/transaction-resource/handler/choice-export-handler?format=csv')
            );
    }
}
