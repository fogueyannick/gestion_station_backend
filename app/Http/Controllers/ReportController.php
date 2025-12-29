<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;      // Use the correct Report model
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * 📥 Crée ou met à jour un rapport journalier (upsert)
     */
    public function store(Request $request)
    {
        \Log::info('RAW SIZE', [
            'content_length' => request()->server('CONTENT_LENGTH'),
        ]);
        $validated = $request->validate([
            'station_id' => 'required|integer|exists:stations,id',
            'date' => 'required|date',

            'super1_index'  => 'required|numeric',
            'super2_index'  => 'required|numeric',
            'super3_index'  => 'required|numeric',
            'gazoil1_index' => 'required|numeric',
            'gazoil2_index' => 'required|numeric',
            'gazoil3_index' => 'required|numeric',

            'stock_sup_9000'  => 'required|integer',
            'stock_sup_10000' => 'required|integer',
            'stock_sup_14000' => 'required|integer',
            'stock_gaz_10000' => 'required|integer',
            'stock_gaz_6000'  => 'required|integer',

            'versement' => 'nullable|numeric',

            'depenses'       => 'nullable|array',
            'autres_ventes'  => 'nullable|array',
            'commandes'      => 'nullable|array',

            'photos'       => 'nullable',
            'photos.*'     => 'file|image|max:5120', // 5 MB
            'photos_keys'  => 'nullable|array',
            'photos_keys.*'=> 'string',


        ]);

        // Préparer les tableaux JSON
        $depenses = array_values($request->input('depenses', []));
        $autresVentes = array_values($request->input('autres_ventes', []));
        $commandes = array_values($request->input('commandes', []));
        
        $rawDate = trim($validated['date']);

        $date = str_contains($rawDate, '/')
            ? Carbon::createFromFormat('d/m/Y', $rawDate)
            : Carbon::parse($rawDate);

        $date = $date->startOfDay();

        // Créer ou mettre à jour le rapport
        $report = Report::updateOrCreate(
            [
                'station_id' => $validated['station_id'],
                'user_id'    => Auth::id(),   // ✅ OBLIGATOIRE
                'date'       => $date,
            ],
            [
                'super1_index'  => $validated['super1_index'],
                'super2_index'  => $validated['super2_index'],
                'super3_index'  => $validated['super3_index'],
                'gazoil1_index' => $validated['gazoil1_index'],
                'gazoil2_index' => $validated['gazoil2_index'],
                'gazoil3_index' => $validated['gazoil3_index'],
                'stock_sup_9000'  => $validated['stock_sup_9000'],
                'stock_sup_10000' => $validated['stock_sup_10000'],
                'stock_sup_14000' => $validated['stock_sup_14000'],
                'stock_gaz_10000' => $validated['stock_gaz_10000'],
                'stock_gaz_6000'  => $validated['stock_gaz_6000'],
                'versement' => $validated['versement'] ?? 0,
                'depenses' => $depenses,
                'autres_ventes' => $autresVentes,
                'commandes' => $commandes,
            ]
        );

        //dd($request->file('photos'), $request->input('photos_keys'));

        // Gérer les photos envoyées depuis Flutter
        \Log::info('FILES TER', [
            'hasPhotos' => $request->hasFile('photos'),
            'files' => $request->file('photos'),
            'keys' => $request->input('photos_keys'),
        ]);

        // Récupérer les photos et leurs clés envoyées par Flutter
        $photos = $request->file('photos');       // tableau d'UploadedFile
        $keys   = $request->input('photos_keys'); // tableau de clés ['super1', 'gaz2', ...]

        // Tableau pour stocker le JSON final
        $storedPhotos = [];

        if ($photos && $keys) {
            foreach ($photos as $index => $photo) {
                $key = $keys[$index] ?? "photo_$index"; // fallback si clé manquante

                // Stocker le fichier dans storage/app/public/reports/{report_id}
                $path = $photo->store("reports/{$report->id}", 'public');

                // Ajouter au tableau de photos à sauvegarder en DB
                $storedPhotos[] = [
                    'key'  => $key,
                    'path' => $path,
                ];
            }
        }

        // Sauvegarder les chemins en JSON dans la colonne 'photos'
        $report->photos = json_encode($storedPhotos);
        $report->save();

        // Log pour vérifier côté serveur
        \Log::info('PHOTOS SAVED', [
            'photos' => $storedPhotos,
        ]);

        //print($photos);


        return response()->json([
            'message' => 'Rapport enregistré',
            'report'  => $report->load('user','station'),
        ], 201);
    }


    /**
     * 📊 Liste de tous les rapports
     */
    public function index()
    {
        return response()->json(
            Report::with('station','user')->orderBy('date', 'desc')->get()
        );
    }

    /**
     * ✏️ Mise à jour d’un rapport
     */
    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'date' => 'sometimes|date',

            'super1_index'  => 'required|numeric',
            'super2_index'  => 'required|numeric',
            'super3_index'  => 'required|numeric',
            'gazoil1_index' => 'required|numeric',
            'gazoil2_index' => 'required|numeric',
            'gazoil3_index' => 'required|numeric',

            'stock_sup_9000'  => 'required|integer',
            'stock_sup_10000' => 'required|integer',
            'stock_sup_14000' => 'required|integer',
            'stock_gaz_10000' => 'required|integer',
            'stock_gaz_6000'  => 'required|integer',

            'versement' => 'nullable|numeric',

            // JSON envoyés par Flutter
            'depenses'       => 'nullable|array',
            'autres_ventes'  => 'nullable|array',
            'commandes'      => 'nullable|array',

            // Photos (multipart)
            'photos.*' => 'nullable|image',
        ]);

        /* -------------------------------
        * Gestion des photos (fusion)
        * ------------------------------- */
        $photos = $report->photos ?? [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $key => $file) {
                $photos[$key] = $file->store('reports', 'public');
            }
        }

        /* -------------------------------
        * Mise à jour du rapport
        * ------------------------------- */
        $report->update([
            'date' => $validated['date'] ?? $report->date,

            'super1_index'  => $validated['super1_index'],
            'super2_index'  => $validated['super2_index'],
            'super3_index'  => $validated['super3_index'],
            'gazoil1_index' => $validated['gazoil1_index'],
            'gazoil2_index' => $validated['gazoil2_index'],
            'gazoil3_index' => $validated['gazoil3_index'],

            'stock_sup_9000'  => $validated['stock_sup_9000'],
            'stock_sup_10000' => $validated['stock_sup_10000'],
            'stock_sup_14000' => $validated['stock_sup_14000'],
            'stock_gaz_10000' => $validated['stock_gaz_10000'],
            'stock_gaz_6000'  => $validated['stock_gaz_6000'],

            'versement' => $validated['versement'] ?? $report->versement,
            'depenses' => $validated['depenses'] ?? $report->depenses,
            'autres_ventes' => $validated['autres_ventes'] ?? $report->autres_ventes,
            'commandes' => $validated['commandes'] ?? $report->commandes,

            'photos' => $photos,
        ]);

        return response()->json([
            'message' => 'Rapport mis à jour avec succès',
            'report'  => $report->load('user','station'),
        ], 200);
    }


    public function destroy($id) {
        $rapport = Report::find($id);
        if (!$rapport) return response()->json(['message' => 'Not found'], 404);
        $rapport->delete();
        return response()->json(['message' => 'Deleted successfully'], 200);
        }


    /**
     * 📈 Statistiques du dashboard
     */
    public function stats()
    {
        $totalReports = Report::count();
        $totalVersements = Report::sum('versement');
        $totalDepenses = Report::sum('depenses');

        return response()->json([
            'total_reports'   => $totalReports,
            'total_versements'=> $totalVersements,
            'total_depenses'  => $totalDepenses,
        ]);
    }

    public function importReports(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new ReportsImport, $request->file('file'));

        return back()->with('success', 'Rapports importés !');
    }
}
