<?php
namespace App\Http\Controllers;
use App\Models\MedicalHistory;
class MedicalHistoryController extends ClinicalHistoryController { protected string $modelClass = MedicalHistory::class; }
