<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentFieldMapping extends Model
{
    protected $fillable = ['assessment_profile_criterion_id','form_field_id','scoring_method','max_score','rubric','answer_key','is_knockout'];
    protected $casts = ['answer_key'=>'array','is_knockout'=>'boolean'];

    public function criterion(){ return $this->belongsTo(AssessmentProfileCriterion::class, 'assessment_profile_criterion_id'); }
    public function field(){ return $this->belongsTo(FormField::class, 'form_field_id'); }
}
