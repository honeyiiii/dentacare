<?php
namespace App\Http\Controllers;

use App\Models\DentalHistory;

class DentalHistoryController extends ClinicalHistoryController
{
    protected string $modelClass = DentalHistory::class;

    protected function categories(): array
    {
        return ['chief_complaint', 'previous_treatment', 'hygiene_habits', 'orthodontic', 'trauma', 'other'];
    }
}
