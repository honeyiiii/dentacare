<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Diagnosis extends Model
{
    protected $fillable = ['patient_id', 'tooth_chart_id', 'dentist_id', 'diagnosis', 'notes'];
}
