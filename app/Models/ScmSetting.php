<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScmSetting extends Model
{
    protected $table = 'scm_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }
}
