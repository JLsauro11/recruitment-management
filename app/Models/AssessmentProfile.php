<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentProfile extends Model
{
    protected $fillable = ['position_id','job_vacancy_id','name','profile_type','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function position(){ return $this->belongsTo(Position::class); }
    public function vacancy(){ return $this->belongsTo(JobVacancy::class, 'job_vacancy_id'); }
    public function criteria(){ return $this->hasMany(AssessmentProfileCriterion::class)->orderBy('sort_order')->orderBy('id'); }
}
