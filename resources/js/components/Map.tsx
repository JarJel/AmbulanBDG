import { useEffect, useRef } from 'react';
import L from 'leaflet';

export interface MapMarker {
    position: [number, number];
    title?: string;
    popup?: string;
    iconType?: 'user' | 'ambulance_emergency' | 'ambulance_non_emergency' | 'ambulance_offline';
}

interface MapProps {
    center: [number, number];
    zoom?: number;
    markers?: MapMarker[];
    routingBetween?: {
        start: [number, number];
        end: [number, number];
        color?: string;
    } | null;
}

export default function Map({ center, zoom = 13, markers = [], routingBetween = null }: MapProps) {
    const mapRef = useRef<HTMLDivElement>(null);
    const mapInstance = useRef<L.Map | null>(null);
    const markersGroup = useRef<L.LayerGroup | null>(null);
    const routingLayer = useRef<L.Polyline | null>(null);

    // Inisialisasi Peta (Hanya Klien / useEffect)
    useEffect(() => {
        if (!mapRef.current) return;

        // Inisialisasi Leaflet Map
        mapInstance.current = L.map(mapRef.current, {
            zoomControl: false // Kita taruh zoom control di posisi pojok kanan bawah agar lebih premium
        }).setView(center, zoom);

        // Taruh zoom control di pojok kanan bawah
        L.control.zoom({ position: 'bottomright' }).addTo(mapInstance.current);

        // Tambah OpenStreetMap Tile Layer (Tema Modern & Ringan)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(mapInstance.current);

        // Inisialisasi Layer Group khusus Penanda (Markers)
        markersGroup.current = L.layerGroup().addTo(mapInstance.current);

        return () => {
            if (mapInstance.current) {
                mapInstance.current.remove();
                mapInstance.current = null;
            }
        };
    }, []);

    // Pembaruan Center / Fokus Peta
    useEffect(() => {
        if (mapInstance.current) {
            mapInstance.current.setView(center, mapInstance.current.getZoom());
        }
    }, [center]);

    const routingKey = routingBetween 
        ? `${routingBetween.start[0]},${routingBetween.start[1]}-${routingBetween.end[0]},${routingBetween.end[1]}-${routingBetween.color}` 
        : '';

    // Pembaruan Jalur Rute Navigasi (Routing)
    useEffect(() => {
        if (!mapInstance.current) return;

        // Hapus rute lama jika ada
        if (routingLayer.current) {
            mapInstance.current.removeLayer(routingLayer.current);
            routingLayer.current = null;
        }

        if (!routingBetween) return;

        const { start, end, color = '#D9383A' } = routingBetween;

        const fetchRoute = async () => {
            try {
                // OSRM menggunakan koordinat format: lng,lat
                const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const coords = data.routes[0].geometry.coordinates.map((coord: [number, number]) => [coord[1], coord[0]]); // ubah ke format Leaflet [lat, lng]
                    
                    if (mapInstance.current) {
                        routingLayer.current = L.polyline(coords, {
                            color: color,
                            weight: 5,
                            opacity: 0.85,
                            lineJoin: 'round'
                        }).addTo(mapInstance.current);
                    }
                }
            } catch (err) {
                console.error("Gagal memuat rute navigasi OSRM:", err);
                
                // Fallback: Gambar garis putus-putus lurus jika OSRM mati
                if (mapInstance.current) {
                    routingLayer.current = L.polyline([start, end], {
                        color: color,
                        weight: 4,
                        dashArray: '5, 10',
                        opacity: 0.7
                    }).addTo(mapInstance.current);
                }
            }
        };

        fetchRoute();

        return () => {
            if (routingLayer.current && mapInstance.current) {
                mapInstance.current.removeLayer(routingLayer.current);
                routingLayer.current = null;
            }
        };
    }, [routingKey]);

    // Pembaruan Penanda Peta (Markers) secara Dinamis
    useEffect(() => {
        if (!mapInstance.current || !markersGroup.current) return;

        // Hapus penanda lama
        markersGroup.current.clearLayers();

        // Gambar penanda baru
        markers.forEach((m) => {
            let icon: L.DivIcon;

            // Bikin Custom Marker menggunakan Tailwind CSS + SVG
            if (m.iconType === 'user') {
                icon = L.divIcon({
                    html: `
                        <div class="relative flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 shadow-xl border-2 border-white">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-40 animate-ping"></span>
                        </div>
                    `,
                    className: '',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12],
                    popupAnchor: [0, -12]
                });
            } else if (m.iconType === 'ambulance_emergency') {
                icon = L.divIcon({
                    html: `
                        <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-red-600 shadow-xl border-2 border-white">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-50 animate-ping"></span>
                        </div>
                    `,
                    className: '',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16]
                });
            } else if (m.iconType === 'ambulance_non_emergency') {
                icon = L.divIcon({
                    html: `
                        <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-600 shadow-xl border-2 border-white">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-30 animate-ping"></span>
                        </div>
                    `,
                    className: '',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16]
                });
            } else {
                // Offline / Suspended / Default
                icon = L.divIcon({
                    html: `
                        <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-slate-500 shadow-xl border-2 border-white">
                        </div>
                    `,
                    className: '',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16]
                });
            }

            const marker = L.marker(m.position, { icon }).addTo(markersGroup.current!);
            if (m.popup) {
                marker.bindPopup(m.popup);
            }
            if (m.title) {
                marker.bindTooltip(m.title, { permanent: false, direction: 'top' });
            }
        });
    }, [markers]);

    return (
        <div 
            ref={mapRef} 
            className="w-full h-full bg-slate-100 relative z-0" 
            style={{ minHeight: '300px' }} 
        />
    );
}
