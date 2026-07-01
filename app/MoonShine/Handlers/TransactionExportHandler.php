<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use Illuminate\Support\Facades\Storage;
use MoonShine\UI\Exceptions\ActionButtonException;
use MoonShine\Crud\Notifications\NotificationButton;
use MoonShine\Laravel\Notifications\MoonShineNotification;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\Response;
use Rap2hpoutre\FastExcel\FastExcel;
use App\MoonShine\Handlers\ExportHandler;
use Generator;

class TransactionExportHandler extends ExportHandler
{
    /**
     * @throws WriterNotOpenedException
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws InvalidArgumentException
     * @throws ResourceException
     */
    public static function process(
        string $path,
        ResourceContract $resource,
        array $query,
        string $disk = 'public',
        string $dir = '/',
        string $delimiter = ',',
        array $notifyUsers = [],
    ): string {
        $resource->setQueryParams($query);

        $items = function (ResourceContract $resource): Generator {
            foreach ($resource->getQuery()->with(['journalEntries.account'])->cursor() as $index => $transaction) {
                if (!$transaction->journalEntries->isEmpty()) {
                    foreach ($transaction->journalEntries as $entry) {
                        $row = [
                            'ID транзакции' => $transaction->id,
                            'Дата' => $transaction->date,
                            'Описание' => $transaction->description,
                            'Создана' => $transaction->created_at,
                            'ID проводки' => $entry->id,
                            'Счёт' => $entry->account?->name ?? 'Не указан',
                            'Сумма' => number_format((float) $entry->amount, 2, '.', ''),
                            'Тип' => $entry->type === 'debit' ? 'Дебет' : 'Кредит',
                        ];

                        yield $row;
                    }
                }
            }
        };

        $fastExcel = new FastExcel($items($resource));

        if (str($path)->contains('.csv')) {
            $fastExcel->configureCsv($delimiter);
        }

        $result = $fastExcel->export($path);

        $url = str($path)
            ->remove(Storage::disk($disk)->path($dir))
            ->value();

        MoonShineNotification::send(
            __('moonshine::ui.resource.export.exported'),
            new NotificationButton(
                label: __('moonshine::ui.download'),
                link: Storage::disk($disk)->url(trim($dir, '/') . $url)
            ),
            ids: $notifyUsers,
        );

        return $result;
    }
}
