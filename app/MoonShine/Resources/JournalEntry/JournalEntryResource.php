<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JournalEntry;

use Illuminate\Database\Eloquent\Model;
use App\Models\JournalEntry;
use App\MoonShine\Resources\JournalEntry\Pages\JournalEntryIndexPage;
use App\MoonShine\Resources\JournalEntry\Pages\JournalEntryFormPage;
use App\MoonShine\Resources\JournalEntry\Pages\JournalEntryDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<JournalEntry, JournalEntryIndexPage, JournalEntryFormPage, JournalEntryDetailPage>
 */
class JournalEntryResource extends ModelResource
{
    protected string $model = JournalEntry::class;

    protected string $title = 'Проводки';
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [

        ];
    }
}
