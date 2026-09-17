<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentProfileCriterion extends Model
{
    protected $fillable = ['assessment_profile_id','name','criterion_key','weight','importance','minimum_score','is_required','description','sort_order','is_active'];
    protected $casts = ['is_required'=>'boolean','is_active'=>'boolean'];

    public function profile(){ return $this->belongsTo(AssessmentProfile::class, 'assessment_profile_id'); }
    public function fieldMappings(){ return $this->hasMany(AssessmentFieldMapping::class, 'assessment_profile_criterion_id'); }
}
