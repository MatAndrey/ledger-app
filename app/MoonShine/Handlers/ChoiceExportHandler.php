<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use MoonShine\ImportExport\ExportHandler;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Throwable;
use MoonShine\UI\Exceptions\ActionButtonException;

class ChoiceExportHandler extends ExportHandler
{
    protected string $format = 'xlsx';

    public function format(string $format): self
    {
        $this->format = $format;
        return $this;
    }

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

        $this->resolveStorage();

        if ($this->format === 'csv') {
            $this->csv();
        }

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

        if ($this->format === 'csv') {
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
        return ActionButton::make($this->getLabel(), $this->getUrl());
    }
}