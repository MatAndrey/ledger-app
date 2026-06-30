<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\MoonShine\Resources\Transaction\Pages\TransactionIndexPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionFormPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionDetailPage;
use App\MoonShine\Handlers\ChoiceExportHandler;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Support\Enums\PageType;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern; 
use MoonShine\ImportExport\ExportHandler;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<Transaction, TransactionIndexPage, TransactionFormPage, TransactionDetailPage>
 */
class TransactionResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = Transaction::class;

    protected string $title = 'Транзакции';

    protected bool $withPolicy = true;
    protected ?PageType $redirectAfterSave = PageType::INDEX;
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            TransactionIndexPage::class,
            TransactionFormPage::class,
            TransactionDetailPage::class,
        ];
    }

    protected function import() {
        return null;
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),
            Date::make('Дата', 'date')->format('d.m.Y'),
            Text::make('Описание', 'description'),
            Text::make('Сумма', 'total_amount')
        ];
    }

    protected function export(): ?Handler
    {
        $format = request()->input('format', 'xlsx');
        return ChoiceExportHandler::make('Экспорт XLSX')
            ->filename(sprintf('transactions_%s', date('Ymd-His')))
            ->format($format);
    }
}
