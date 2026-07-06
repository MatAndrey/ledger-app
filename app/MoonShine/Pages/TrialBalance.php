<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Components\ActionButton;
use App\Services\AccountService;
use Carbon\Carbon;
use MoonShine\Support\Enums\FormMethod;
use App\Enums\AccountTypes;
use MoonShine\Support\Attributes\AsyncMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class TrialBalance extends Page
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
        return $this->title ?: 'Оборотно-сальдовая ведомость';
    }

    #[AsyncMethod]
    public function export(AccountService $accountService): BinaryFileResponse {
        $start = request('start', Carbon::now()->startOfMonth()->toDateString());
        $end = request('end', Carbon::now()->endOfMonth()->toDateString());
        $format = request('format');

        if ($format === 'csv' || $format === 'xlsx') {
            $path = $accountService->generateTrialBalanceFile(Carbon::parse($start), Carbon::parse($end), $format);
            $filename = 'trial_balance_' . $start . '_' . $end . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
	{
		$start = request('date.start', Carbon::now()->startOfMonth()->toDateString());
        $end   = request('date.end', Carbon::now()->endOfMonth()->toDateString());

        $service = app(AccountService::class);
        $reportData = $service->generateTrialBalance(
            Carbon::parse($start),
            Carbon::parse($end)
        );
        

        return [
            Grid::make([
                Column::make([
                    FormBuilder::make()
                        ->method(FormMethod::GET)
                        ->fields([
                            DateRange::make('Период', 'date')
                                ->fromTo('start', 'end')
                        ])
                        ->submit('Показать', ['class' => 'btn-primary'])
                        ->buttons([
                            ActionButton::make('Экспорт CSV')
                                ->method('export', [
                                    'format' => 'csv',
                                    'start' => $start,
                                    'end' => $end
                                    ])
                                ->download()
                                ->icon('document-text'),
                            ActionButton::make('Экспорт XLSX')
                                ->method('export', [
                                    'format' => 'xlsx',
                                    'start' => $start,
                                    'end' => $end
                                    ])
                                ->download()
                                ->icon('table-cells'),
                        ])
                ])->columnSpan(12),

                Column::make([
                    TableBuilder::make()
                        ->fields([
                            Text::make('Код', 'account.code'),
                            Text::make('Счёт', 'account.name'),
                            Enum::make('Тип', 'account.type')->attach(AccountTypes::class),
                            Text::make('Сальдо на начало (Дебет)', 'opening_debit'),
                            Text::make('Сальдо на начало (Кредит)', 'opening_credit'),
                            Text::make('Оборот (Дебет)', 'debit_turnover'),
                            Text::make('Оборот (Кредит)', 'credit_turnover'),
                            Text::make('Сальдо на конец (Дебет)', 'closing_debit'),
                            Text::make('Сальдо на конец (Кредит)', 'closing_credit'),
                        ])
                        ->items($reportData)
                        ->when(empty($reportData), function ($table) {
                            $table->withNotFound('Нет данных за выбранный период');
                        })
                ])->columnSpan(12),
            ]),
        ];
	}
}
