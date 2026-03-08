<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearLevel extends Model
{
    protected $fillable = ['name', 'term_type'];

    public function terms()
    {
        return $this->belongsToMany(Term::class, 'term_year_level')->withTimestamps();
    }
}
