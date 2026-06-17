<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Designation::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:designations,name|max:100',
            'department_id' => 'required|exists:departments,id',
        ]);
        $designation = Designation::create($validated);

        return response()->json($designation, 201);
    }

    public function show(Designation $designation)
    {
        return response()->json($designation);
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:designations,name,'.$designation->id.'|max:100',
            'department_id' => 'required|exists:departments,id',
        ]);
        $designation->update($validated);

        return response()->json($designation);
    }

    public function destroy(Designation $designation)
    {
        $designation->delete();

        return response()->json(['message' => 'Designation deleted.']);
    }
}
