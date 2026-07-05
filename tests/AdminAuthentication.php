<?php

namespace Tests;

use MoonShine\Laravel\Models\MoonshineUser;

trait AdminAuthentication
{
    protected function authenticateAdmin()
    {
        $user = MoonshineUser::factory()->create();
        $this->actingAs($user, 'moonshine');
        return $user;
    }
}