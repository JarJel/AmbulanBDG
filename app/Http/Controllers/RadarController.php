<?php

namespace App\Http\Controllers;

use App\Models\Ambulance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RadarController extends Controller
{
    /**
     * Cari seluruh armada ambulans yang siaga online (standby) dalam radius tertentu.
     */
    public function getRadar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|numeric|min:0.1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $radiusMeters = ((float) $request->input('radius_km', 10.0)) * 1000;

        // Query spatial data dengan ST_Distance_Sphere
        $ambulances = Ambulance::with('institution')
            ->selectRaw("*, ST_X(current_location) as longitude, ST_Y(current_location) as latitude, ST_Distance_Sphere(current_location, Point(?, ?)) as distance", [$longitude, $latitude])
            ->where('status', '!=', 'offline')
            ->having('distance', '<=', $radiusMeters)
            ->orderBy('distance', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_km' => $request->input('radius_km', 10.0),
            'results_count' => $ambulances->count(),
            'ambulances' => $ambulances,
        ]);
    }

    /**
     * Mengonversi nama tempat/alamat menjadi koordinat GPS (Geocoding Mock).
     */
    public function getGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address = strtolower($request->address);
        $latitude = -6.917464; // Default Bandung Center
        $longitude = 107.619123;
        $formattedAddress = $request->address . ", Kota Bandung, Jawa Barat, Indonesia";

        if (str_contains($address, 'gedung sate')) {
            $latitude = -6.902481;
            $longitude = 107.618810;
            $formattedAddress = "Gedung Sate, Jl. Diponegoro No.22, Citarum, Kec. Bandung Wetan, Kota Bandung, Jawa Barat 40115";
        } elseif (str_contains($address, 'dago') || str_contains($address, 'riau')) {
            $latitude = -6.891480;
            $longitude = 107.616490;
            $formattedAddress = "Dago, Jl. Ir. H. Juanda No.1, Dago, Kecamatan Coblong, Kota Bandung, Jawa Barat 40132";
        } elseif (str_contains($address, 'cihampelas')) {
            $latitude = -6.896582;
            $longitude = 107.604215;
            $formattedAddress = "Cihampelas Walk, Jl. Cihampelas No.160, Cipaganti, Kecamatan Coblong, Kota Bandung, Jawa Barat 40131";
        }

        return response()->json([
            'status' => 'success',
            'input_address' => $request->address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'formatted_address' => $formattedAddress,
        ]);
    }

    /**
     * Mengonversi koordinat GPS menjadi nama jalan/alamat tekstual (Reverse Geocoding Mock).
     */
    public function getReverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;

        // Hitung jarak sederhana ke spot-spot populer Bandung
        $formattedAddress = "Jl. Ir. H. Juanda No.1, Dago, Kecamatan Coblong, Kota Bandung, Jawa Barat 40132";

        // Gedung Sate coordinates
        $distToGedungSate = sqrt(pow($latitude - (-6.902481), 2) + pow($longitude - 107.618810, 2));
        // Cihampelas Walk coordinates
        $distToCihampelas = sqrt(pow($latitude - (-6.896582), 2) + pow($longitude - 107.604215, 2));

        if ($distToGedungSate < 0.005) {
            $formattedAddress = "Gedung Sate, Jl. Diponegoro No.22, Citarum, Kec. Bandung Wetan, Kota Bandung, Jawa Barat 40115";
        } elseif ($distToCihampelas < 0.005) {
            $formattedAddress = "Cihampelas Walk, Jl. Cihampelas No.160, Cipaganti, Kecamatan Coblong, Kota Bandung, Jawa Barat 40131";
        }

        return response()->json([
            'status' => 'success',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'formatted_address' => $formattedAddress,
        ]);
    }
}
