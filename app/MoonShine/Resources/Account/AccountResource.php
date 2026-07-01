<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Account;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\MoonShine\Resources\Account\Pages\AccountIndexPage;
use App\MoonShine\Resources\Account\Pages\AccountFormPage;
use App\MoonShine\Resources\Account\Pages\AccountDetailPage;
use App\MoonShine\Handlers\ExportHandler;
use MoonShine\Support\ListOf;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Enum;
use App\Enums\AccountTypes;

/**
 * @extends ModelResource<Account, AccountIndexPage, AccountFormPage, AccountDetailPage>
 */
class AccountResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern;
    
    protected string $model = Account::class;

    protected string $title = 'Счета';
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            AccountIndexPage::class,
            AccountFormPage::class,
            AccountDetailPage::class,
        ];
    }

    protected function import(): ?Handler
    {
        return null;
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Код', 'code'),
            Text::make('Название', 'name'),
            Enum::make('Тип', 'type')->attach(AccountTypes::class),
            Checkbox::make('Активен', 'is_active')
                ->onValue('Да')
                ->offValue('Нет'),
        ];
    }

    private function getExportFileName(): string {
        return sprintf('accounts_%s', date('Ymd-His'));
    }

    protected function export(): ?Handler
    {
        return ExportHandler::make('Экспорт XLSX')
            ->filename($this->getExportFileName());
    }

    protected function exportCSV(): ?Handler
    {
        return ExportHandler::make('Экспорт CSV')
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
