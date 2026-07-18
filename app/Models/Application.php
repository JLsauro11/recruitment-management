<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Application extends Model { use HasFactory; protected $fillable=['applicant_id','job_vacancy_id','reference_no','status','remarks','applied_at']; protected $casts=['applied_at'=>'datetime']; public function applicant(){return $this->belongsTo(Applicant::class);} public function vacancy(){return $this->belongsTo(JobVacancy::class,'job_vacancy_id');} public function interviews(){return $this->hasMany(Interview::class);} public function examResults(){return $this->hasMany(ExamResult::class);} public function statusHistories(){return $this->hasMany(ApplicationStatusHistory::class);} }
