<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobVacancyQualification extends Model
{
    protected $fillable = [
        'job_vacancy_id',
        'qualification_text',
        'qualification_type',
        'requirement_level',
        'minimum_value',
        'minimum_unit',
        'evidence_source',
        'importance',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'minimum_value' => 'float',
        'is_active' => 'boolean',
    ];

    public function vacancy()
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }
}
