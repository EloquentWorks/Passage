<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Tests\Fixtures;

use EloquentWorks\Passage\Traits\HasPassages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

final class User extends Model
{
    use HasPassages;
    use Notifiable;

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'email_verified'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['email_verified' => 'boolean'];
    }
}
