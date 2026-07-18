<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Department extends Model { use HasFactory; protected $fillable=['name','description','status']; public function positions(){return $this->hasMany(Position::class);} }
