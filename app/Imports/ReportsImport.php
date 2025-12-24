<?php

namespace App\Imports;

use App\Models\Report;
use App\Models\Station;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ReportsImport implements ToModel, WithHeadingRow
{
    // Convertit une valeur Excel en tableau valide pour JSON
    private function toArray($value): array
    {
        if (is_null($value) || $value === '') return [];

        // Si c'est déjà un JSON valide
        if (is_string($value) && str_starts_with(trim($value), '[')) {
            return json_decode($value, true) ?? [];
        }

        // Si c'est une chaîne CSV : "100,200,300"
        if (is_string($value) && strpos($value, ',') !== false) {
            return array_map(fn($v) => is_numeric($v) ? (float)$v : $v, explode(',', $value));
        }

        // Si c'est un nombre
        if (is_numeric($value)) return [(float)$value];

        return [];
    }

    public function model(array $row)
    {
        $stationId = Station::find($row['station_id'] ?? null) ? (int) $row['station_id'] : 1;

        $date = now();
        if (isset($row['date'])) {
            if (is_numeric($row['date'])) {
                $date = ExcelDate::excelToDateTimeObject($row['date']);
            } else {
                try {
                    $date = Carbon::parse($row['date']);
                } catch (\Exception $e) {
                    $date = now();
                }
            }
        }

        return Report::updateOrCreate(
            [
                'station_id' => $stationId,
                'user_id'    => 1,
                'date'       => $date,
            ],
            [
                'super1_index'    => (double) ($row['super1_index'] ?? 0),
                'super2_index'    => (double) ($row['super2_index'] ?? 0),
                'super3_index'    => (double) ($row['super3_index'] ?? 0),
                'gazoil1_index'   => (double) ($row['gazoil1_index'] ?? 0),
                'gazoil2_index'   => (double) ($row['gazoil2_index'] ?? 0),
                'gazoil3_index'   => (double) ($row['gazoil3_index'] ?? 0),
                'stock_sup_9000'  => (int) ($row['stock_sup_9000'] ?? 0),
                'stock_sup_10000' => (int) ($row['stock_sup_10000'] ?? 0),
                'stock_sup_14000' => (int) ($row['stock_sup_14000'] ?? 0),
                'stock_gaz_10000' => (int) ($row['stock_gaz_10000'] ?? 0),
                'stock_gaz_6000'  => (int) ($row['stock_gaz_6000'] ?? 0),
                'versement'       => (float) ($row['versement'] ?? 0),
                'depenses'        => $this->toArray($row['depenses'] ?? null),
                'autres_ventes'   => $this->toArray($row['autres_ventes'] ?? null),
                'commandes'       => $this->toArray($row['commandes'] ?? null),
                'photos'          => $this->toArray($row['photos'] ?? null),
            ]
        );
    }

}
