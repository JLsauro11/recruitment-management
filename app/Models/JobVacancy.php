<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class JobVacancy extends Model { use HasFactory; protected $fillable=['position_id','title','slots','employment_type','description','qualifications','salary_min','salary_max','opening_date','closing_date','status']; protected $casts=['opening_date'=>'date','closing_date'=>'date']; public function position(){return $this->belongsTo(Position::class);} public function applications(){return $this->hasMany(Application::class);} }
