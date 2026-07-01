<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\MoonShine\Resources\Transaction\Pages\TransactionIndexPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionFormPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionDetailPage;
use App\MoonShine\Handlers\TransactionExportHandler;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\ListOf;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern; 
use MoonShine\Crud\Handlers\Handler;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use App\MoonShine\Handlers\ExportHandler;

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

    private function getExportFileName(): string {
        return sprintf('transactions_%s', date('Ymd-His'));
    }

    protected function export(): ?Handler
    {
        return TransactionExportHandler::make('Экспорт XLSX')
            ->filename($this->getExportFileName());
    }

    protected function exportCSV(): ?Handler
    {
        return TransactionExportHandler::make('Экспорт CSV')
            ->filename($this->getExportFileName())
            ->csv();
    }

    protected function handlers(): ListOf
    {
        return new ListOf(Handler::class, array_filter([
            $this->export(),
            $this->exportCSV(),
        ]));
    }
}
