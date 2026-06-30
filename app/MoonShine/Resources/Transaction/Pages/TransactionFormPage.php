<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction\Pages;

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Fields\Relationships\RelationRepeater;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Transaction\TransactionResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Textarea;
use App\MoonShine\Resources\JournalEntry\JournalEntryResource;
use App\Models\Account;
use App\Enums\JournalEntryTypes;
use Throwable;


/**
 * @extends FormPage<TransactionResource>
 */
class TransactionFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Date::make('Дата', 'date')
                ->required()
                ->default(now()->toDateString()),
            Textarea::make('Описание', 'description'),
            RelationRepeater::make('Проводки', 'journalEntries', resource: JournalEntryResource::class)
                ->fields([
                    Select::make('Счёт', 'account_id')
                        ->options(Account::where('is_active', true)->pluck('name', 'id')->toArray())
                        ->required()
                        ->searchable(),
                    Number::make('Сумма', 'amount')
                        ->required()
                        ->min(0)
                        ->placeholder('0'),
                    Enum::make('Тип', 'type')
                        ->attach(JournalEntryTypes::class)
                        ->required()
                ])
                ->creatable()
                ->removable(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'journalEntries' => ['required', 'array', 'min:2', function (string $attribute, mixed $value, \Closure $fail) {
                $totalDebit = 0;
                $totalCredit = 0;
                foreach($value as $entry) {
                    if($entry['type'] == 'debit') $totalDebit += $entry['amount'];
                    if($entry['type'] == 'credit') $totalCredit += $entry['amount'];
                }

                if(abs($totalCredit - $totalDebit) > 0.0001) {
                    $fail("Сумма дебета ($totalDebit) должна равняться сумме кредита ($totalCredit)");
                }
            }],
            'journalEntries.*.type' => ['required', 'in:debit,credit'],
            'journalEntries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'journalEntries.*.account_id' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $account = Account::find($value);
                    if (!$account || !$account->is_active) {
                        $fail("Выбранный счёт ($account[name]) неактивен.");
                    }
                },
            ],
        ];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
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
}
