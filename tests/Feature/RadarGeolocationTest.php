<?php

use App\Models\Ambulance;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;

test('it filters online standby ambulances within search radius correctly', function () {
    // 1. Setup Institution
    $institution = Institution::create([
        'name' => 'RS Geospasial',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    // 2. Setup Ambulances with varying distances from Gedung Sate (-6.902481, 107.618810)
    // Ambulance A: ~300m away (Very close)
    $ambA = new Ambulance();
    $ambA->institution_id = $institution->id;
    $ambA->plate_number = 'D 1111 AA';
    $ambA->category = 'emergency';
    $ambA->status = 'standby';
    $ambA->current_location = DB::raw("Point(107.618, -6.905)");
    $ambA->save();

    // Ambulance B: ~3 KM away (Within 5 KM)
    $ambB = new Ambulance();
    $ambB->institution_id = $institution->id;
    $ambB->plate_number = 'D 2222 BB';
    $ambB->category = 'emergency';
    $ambB->status = 'standby';
    $ambB->current_location = DB::raw("Point(107.61, -6.93)");
    $ambB->save();

    // Ambulance C: ~15 KM away (Outside 5 KM)
    $ambC = new Ambulance();
    $ambC->institution_id = $institution->id;
    $ambC->plate_number = 'D 3333 CC';
    $ambC->category = 'emergency';
    $ambC->status = 'standby';
    $ambC->current_location = DB::raw("Point(107.55, -7.02)");
    $ambC->save();

    // Ambulance D: Very close, but OFFLINE (should be excluded)
    $ambD = new Ambulance();
    $ambD->institution_id = $institution->id;
    $ambD->plate_number = 'D 4444 DD';
    $ambD->category = 'emergency';
    $ambD->status = 'offline';
    $ambD->current_location = DB::raw("Point(107.618, -6.905)");
    $ambD->save();

    // 3. Call Radar API with 5 KM radius
    $response = $this->getJson('/api/radar?latitude=-6.902481&longitude=107.618810&radius_km=5');

    $response->assertStatus(200);
    $response->assertJsonPath('results_count', 2);

    // Verify ordering is based on distance ascending (closest first)
    $results = $response->json('ambulances');
    expect($results[0]['plate_number'])->toBe('D 1111 AA');
    expect($results[1]['plate_number'])->toBe('D 2222 BB');
});

test('it handles geocoding and reverse geocoding mocks', function () {
    // A. Geocoding
    $responseGeo = $this->getJson('/api/geocode?address=Gedung+Sate');
    $responseGeo->assertStatus(200);
    expect($responseGeo->json('latitude'))->toBe(-6.902481);
    expect($responseGeo->json('longitude'))->toBe(107.618810);
    expect($responseGeo->json('formatted_address'))->toContain('Gedung Sate');

    // B. Reverse Geocoding
    $responseRev = $this->getJson('/api/reverse-geocode?latitude=-6.902481&longitude=107.618810');
    $responseRev->assertStatus(200);
    expect($responseRev->json('formatted_address'))->toContain('Gedung Sate');
});
