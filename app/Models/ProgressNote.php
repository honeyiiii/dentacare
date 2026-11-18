<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProgressNote extends Model
{
    protected $fillable = ['treatment_id', 'note', 'author_id'];
}
