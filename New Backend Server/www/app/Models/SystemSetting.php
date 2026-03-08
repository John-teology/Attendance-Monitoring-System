<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_name',
        'school_logo',
        'app_background_image',
        'school_name_color',
        'button_bg_color',
        'body_bg_color',
        'font_style',
        'card_transparency',
        'button_transparency',
        'icon_color',
        'volume_level',
    ];
}
