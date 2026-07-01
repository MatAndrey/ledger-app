<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use MoonShine\UI\Exceptions\ActionButtonException;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class ExportHandler extends \MoonShine\ImportExport\ExportHandler
{
    /**
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws UnsupportedTypeException
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function handle(): Response
    {
        if (! $this->hasResource()) {
            throw ActionButtonException::resourceRequired();
        }

        if (request()->query('format') === 'csv') {
            $this->csv();
        }

        $this->resolveStorage();

        $path = Storage::disk($this->getDisk())->path($this->generateFilePath());

        $query = collect(request()->query())->except(['_component_name', 'page'])->toArray();

        $file = static::process(
            $path,
            $this->getResource(),
            $query,
            $this->getDisk(),
            $this->getDir(),
            $this->getDelimiter(),
            $this->getNotifyUsers(),
        );

        if($this->isCsv()) {
            $this->addBomToCsv($path);
        }

        return response()->download($path, basename($path));
    }

    protected function addBomToCsv(string $path): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return;
        }

        $content = "\xEF\xBB\xBF" . $content;
        file_put_contents($path, $content);
    }

    public function getButton(): ActionButtonContract
    {
        if (! $this->hasResource()) {
            throw ActionButtonException::resourceRequired();
        }

        $query = Arr::query(request(['filter', 'sort', 'query-tag'], []));
        $url = $this->getUrl();
        $originalQuery = "ts=" . time();

        $originalQuery .= '&format=' . ($this->isCsv() ? 'csv' : 'xlsx');

        $attributes = [
            'class' => 'js-change-query',
            'data-original-url' => $url,
            'data-original-query' => $originalQuery,
        ];

        $button = ActionButton::make(
            $this->getLabel(),
            $url = trim("$url?$originalQuery&$query", '&')
        )
            ->primary()
            ->customAttributes($attributes)
            ->icon($this->isCsv() ? 'document-text' : 'table-cells');

        if ($this->isWithConfirm()) {
            $button->withConfirm(
                formBuilder: static fn (FormBuilder $form): FormBuilder => $form->customAttributes($attributes)
            );
        }

        return $this->prepareButton($button);
    }
}
