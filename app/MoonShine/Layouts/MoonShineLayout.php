<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\Account\AccountResource;
use MoonShine\MenuManager\MenuItem;
use App\MoonShine\Resources\Transaction\TransactionResource;
use App\MoonShine\Resources\JournalEntry\JournalEntryResource;
use App\MoonShine\Pages\TrialBalance;
use App\MoonShine\Resources\User\UserResource;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        return [
            ...parent::menu(),            
            MenuItem::make(UserResource::class, 'Пользователи'),
            MenuItem::make(AccountResource::class, 'Счета'),
            MenuItem::make(TransactionResource::class, 'Транзакции'),
            MenuItem::make(TrialBalance::class, 'ОСВ'),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
