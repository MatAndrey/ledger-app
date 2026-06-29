<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Model;


#[Fillable(['name', 'code', 'type', 'is_active'])]
#[Cast('type', AccountTypes::class)]
#[Cast('is_active', 'boolean')]
#[Cast('code', 'integer')]
class Account extends Model
{

}
