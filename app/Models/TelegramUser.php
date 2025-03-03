<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    use HasFactory;

    /**
     * Название таблицы, связанной с моделью.
     *
     * @var string
     */
    protected $table = 'telegram_user';

    /**
     * Поля, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = [
        'banned',
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'role',
    ];

    /**
     * Поля, которые должны быть скрыты при сериализации.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
