<?php

namespace App\Services;

use App\Models\Ambulance;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ValidateLocationUpdate
{
    // Kecepatan maksimum wajar untuk ambulans dalam kondisi darurat (m/s)
    // ~120 km/jam saat SOS, dilonggarkan dari kecepatan normal kota
    private const MAX_SPEED_MPS = 33.33; // 33.33 m/s = 120 km/h

    /**
     * Validate if the location update is physically realistic.
     *
     * @param Ambulance $ambulance
     * @param float $newLat
     * @param float $newLng
     * @param CarbonInterface $timestamp
     * @return bool
     */
    public function isValid(Ambulance $ambulance, float $newLat, float $newLng, CarbonInterface $timestamp): bool
    {
        $last = $ambulance->last_location_at;
        if (!$last) {
            return true; // first update, nothing to compare
        }

        // Securely fetch X (lng) and Y (lat) of current location from database
        $coords = DB::selectOne("SELECT ST_X(current_location) as lng, ST_Y(current_location) as lat FROM ambulances WHERE id = ?", [$ambulance->id]);
        if (!$coords) {
            return true;
        }

        $distanceMeters = $this->haversine(
            (float) $coords->lat,
            (float) $coords->lng,
            $newLat,
            $newLng
        );

        $elapsedSeconds = $timestamp->diffInSeconds($last);
        if ($elapsedSeconds <= 0) {
            return false; // timestamp duplicate/backward, reject
        }

        $impliedSpeed = $distanceMeters / $elapsedSeconds;

        return $impliedSpeed <= self::MAX_SPEED_MPS;
    }

    /**
     * Calculate Haversine distance between two coordinates in meters.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
