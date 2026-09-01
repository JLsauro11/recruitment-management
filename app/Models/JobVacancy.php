<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'title',
        'slots',
        'employment_type',
        'description',
        'qualifications',
        'salary_min',
        'salary_max',
        'opening_date',
        'closing_date',
        'status',
        'poster_path',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
    ];

    /**
     * Vacancies that are currently accepting applications.
     *
     * A vacancy is open only when its manual status is Open, its opening date
     * has arrived, and its closing date has not passed.
     */
    public function scopeOpenForApplications(Builder $query): Builder
    {
        return $query
            ->where('status', 'Open')
            ->whereDate('opening_date', '<=', today())
            ->where(function (Builder $query) {
                $query->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', today());
            });
    }

    /**
     * Keep expired vacancies consistent in the database whenever vacancy data
     * is viewed. This avoids stale "Open" labels after the closing date passes.
     */
    public static function syncExpiredStatuses(): int
    {
        return static::query()
            ->where('status', 'Open')
            ->whereNotNull('closing_date')
            ->whereDate('closing_date', '<', today())
            ->update(['status' => 'Closed']);
    }

    public function isExpired(): bool
    {
        return $this->closing_date !== null && $this->closing_date->lt(today());
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'Open' && $this->isExpired()) {
            return 'Closed';
        }

        return ucfirst(strtolower((string) $this->status));
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
