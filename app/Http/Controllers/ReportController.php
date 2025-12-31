<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;      // Use the correct Report model
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Services\GcsUploader;

use Google\Cloud\Storage\StorageClient;


class ReportController extends Controller
{
    /**
     * 📥 Crée ou met à jour un rapport journalier (upsert)
     */
    public function store(Request $request)
    {
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

            // Pour Option 2 : photos envoyées via signed URL
            'photos_keys' => 'nullable|array',
            'photos_keys.*' => 'string',
            'photos_urls' => 'nullable|array',
            'photos_urls.*' => 'url',
        ]);

        // Préparer les tableaux JSON
        $depenses = array_values($request->input('depenses', []));
        $autresVentes = array_values($request->input('autres_ventes', []));
        $commandes = array_values($request->input('commandes', []));

        // Parse date
        $rawDate = trim($validated['date']);
        $date = str_contains($rawDate, '/')
            ? Carbon::createFromFormat('d/m/Y', $rawDate)
            : Carbon::parse($rawDate);
        $date = $date->startOfDay();

        // Créer ou mettre à jour le rapport
        $report = Report::updateOrCreate(
            [
                'station_id' => $validated['station_id'],
                'user_id'    => Auth::id(),
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

        // -------------------------
        // Photos Option 2 : URLs
        // -------------------------
        $photosKeys = $request->input('photos_keys', []);
        $photosUrls = $request->input('photos_urls', []);

        $storedPhotos = [];
        for ($i = 0; $i < count($photosKeys); $i++) {
            if (!empty($photosKeys[$i]) && !empty($photosUrls[$i])) {
                $storedPhotos[] = [
                    'key' => $photosKeys[$i],
                    'url' => $photosUrls[$i],
                ];
            }
        }

        $report->photos = json_encode($storedPhotos);
        $report->save();

        // Log pour debug
        \Log::info('REPORT STORED', [
            'report_id' => $report->id,
            'photos' => $storedPhotos,
        ]);

        return response()->json([
            'message' => 'Rapport enregistré',
            'report'  => $report->load('user','station'),
        ], 201);
    }



    public function signedUrl(Request $request)
    {
        $request->validate([
            'key' => 'required|string',       // ex: super1
            'extension' => 'required|string'  // ex: jpg
        ]);

        $storage = new StorageClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT'),
            'keyFilePath' => storage_path('app/gcs-key.json'),
        ]);

        $bucket = $storage->bucket(env('GOOGLE_CLOUD_BUCKET'));

        // Génère un nom unique pour le fichier
        $objectName = 'reports/' . uniqid() . '.' . $request->extension;

        $object = $bucket->object($objectName);

        // Générer le signed URL pour upload (PUT)
        $uploadUrl = $object->signedUrl(
            now()->addMinutes(10),         // expire dans 10 min
            [
                'method' => 'PUT',
                'contentType' => 'image/jpeg',
            ]
        );

        // URL publique qui sera stockée en DB
        $publicUrl = "https://storage.googleapis.com/" . env('GOOGLE_CLOUD_BUCKET') . "/" . $objectName;

        return response()->json([
            'upload_url' => $uploadUrl,
            'public_url' => $publicUrl,
            'objectName' => $objectName,
        ]);
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
