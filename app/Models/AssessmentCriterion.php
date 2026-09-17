<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentCriterion extends Model {
    protected $fillable=['job_vacancy_id','name','weight','field_keywords','description','sort_order','is_active'];
    protected $casts=['field_keywords'=>'array','is_active'=>'boolean'];
    public function vacancy(){ return $this->belongsTo(JobVacancy::class,'job_vacancy_id'); }
}
