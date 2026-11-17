<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $patients = Patient::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'sex'            => ['required', 'in:Male,Female'],
            'contact_number' => ['required', 'string', 'max:20'],
            'address'        => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
        ]);

        Patient::create($data);

        return redirect()->route('patients.index')
            ->with('success', 'Patient registered successfully.');
    }
}