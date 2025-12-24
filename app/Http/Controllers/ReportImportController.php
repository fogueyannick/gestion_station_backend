<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReportsImport;

class ReportImportController extends Controller
{
    public function showForm()
    {
        return view('reports.import'); // La vue pour uploader
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new ReportsImport, $request->file('file'));

        return back()->with('success', 'Rapports importés avec succès !');
    }
}
