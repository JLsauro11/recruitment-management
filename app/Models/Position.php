<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Position extends Model { use HasFactory; protected $fillable=['department_id','name','description','status']; public function department(){return $this->belongsTo(Department::class);} public function vacancies(){return $this->hasMany(JobVacancy::class);} public function assessmentProfiles(){return $this->hasMany(AssessmentProfile::class);} public function defaultAssessmentProfile(){return $this->hasOne(AssessmentProfile::class)->where('profile_type','position_default');} }
