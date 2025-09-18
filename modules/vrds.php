<?php

// VEHICLE RESERVATION AND DISPATCH SYSTEM (VRDS)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/../includes/vrds_logic.php';
require_once __DIR__ . '/../includes/ajax.php';

function vrds_view($baseURL)
{

    $role = $_SESSION['role'];

    vrds_logic($baseURL);
    $requests = fetchAll('vehicle_requests');
    $dispatches = fetchAll('dispatches');
    $vehicles = fetchAll('fleet_vehicles');
    $drivers = fetchAll('drivers');

    // Get unique vehicle types for dropdown
    $vehicle_types = [];
    foreach ($vehicles as $v) {
        if (!empty($v['vehicle_type']) && !in_array($v['vehicle_type'], $vehicle_types)) {
            $vehicle_types[] = $v['vehicle_type'];
        }
    }


    // Prepare ongoing dispatches for map (with real coordinates)
    $ongoingDispatches = array_filter($dispatches, function ($d) {
        return $d['status'] === 'Ongoing' && isset($d['origin_lat'], $d['origin_lon'], $d['destination_lat'], $d['destination_lon']);
    });
?>

    <div>
        <!-- OSM Map for Ongoing Dispatched Trips -->
        <div class="mb-6">
            <?php if (!in_array($role, ['requester', 'user'])): ?>
                <h3 class="text-lg font-bold mb-2">Dispatched Trips Map</h3>
            <?php endif; ?>
            <div class="flex flex-wrap gap-2 mb-2">
                <input id="mapSearch" class="input input-bordered" style="min-width:220px;max-width:350px;" placeholder="Search a place.." autocomplete="off">
                <div id="searchSuggestions" class="osm-suggestions" style="position:absolute;z-index:1000;"></div>
            </div>
            <div class="flex flex-wrap gap-2 mb-2">
                <button id="addPoiBtn" class="btn btn-sm btn-success" type="button"><i data-lucide="map-pin-plus"></i> Add a Custom Location </button>
            </div>
            <div id="dispatchMap" style="height: 400px; width: 100%;"></div>
        </div>

        <?php if (!in_array($role, ['requester', 'user'])): ?>
            <h2 class="text-lg md:text-2xl font-bold mb-4">Vehicle Reservation & Dispatch</h2>
        <?php endif; ?>

        <!-- Vehicle Request Form (Step 1) -->
        <div class="flex flex-col gap-2">

            <div class="flex gap-2 flex-wrap">
                <?php if (in_array($role, ['requester', 'user'])): ?>

                    <button class="btn btn-primary w-max" onclick="request_modal.showModal()">
                        <i data-lucide="plus-circle" class="w-4 h-4 mr-1"></i> Request Vehicle
                    </button>
                <?php endif; ?>
                <?php if (!in_array($role, ['requester', 'user'])): ?>
                    <button class="btn btn-secondary w-max" onclick="dispatch_log_modal.showModal()">
                        <i data-lucide="list" class="w-4 h-4 mr-1"></i> View Dispatch Log
                    </button>
                <?php endif; ?>
            </div>


            <?php if (in_array($role, ['requester', 'user'])): ?>
                <?php require_once __DIR__ . '/../includes/requester_history.php'; ?>


                <dialog id="request_modal" class="modal">
                    <div class="modal-box">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                        </form>
                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6">
                            <input type="hidden" name="request_vehicle" value="1">
                            <div class="form-control mb-2">
                                <label class="label">Purpose</label>
                                <input type="text" name="purpose" class="input input-bordered" required>
                            </div>
                            <div class="form-control mb-2">
                                <label class="label">Origin</label>
                                <input type="text" name="origin" id="origin" class="input input-bordered osm-autocomplete" autocomplete="off" required>
                                <div id="origin-suggestions" class="osm-suggestions"></div>
                            </div>
                            <!-- Map View Between Origin and Destination -->
                            <div class="form-control mb-2">
                                <div id="osm-map" style="height: 300px; width: 100%; margin-bottom: 8px;"></div>
                            </div>
                            <div class="form-control mb-2">
                                <label class="label">Destination</label>
                                <input type="text" name="destination" id="destination" class="input input-bordered osm-autocomplete" autocomplete="off" required>
                                <div id="destination-suggestions" class="osm-suggestions"></div>
                            </div>
                            <div class="form-control mb-2">
                                <label class="label">Requested Vehicle Type</label>
                                <select name="requested_vehicle_type" class="select select-bordered" required>
                                    <option value="">Select vehicle type</option>
                                    <?php foreach ($vehicle_types as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-control mb-2">
                                <label class="label">Reservation Date</label>
                                <input type="date" name="reservation_date" class="input input-bordered" required>
                            </div>
                            <div class="form-control mb-2">
                                <label class="label">Expected Return Date</label>
                                <input type="date" name="expected_return" class="input input-bordered" required>
                            </div>
                            <button class="btn btn-primary btn-outline mt-2 w-full">Submit Request</button>
                        </form>
                </dialog>
            <?php endif; ?>

            <?php if (!in_array($role, ['requester', 'user'])): ?>
                <!-- Pending Requests Table (For Transport Officer Approval) -->
                <h3 class="text-md md:text-xl font-bold mt-6 mb-2">Pending Vehicle Requests</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Purpose</th>
                                <th>Origin</th>
                                <th>Destination</th>
                                <th>Requested Vehicle Type</th>
                                <th>Reservation Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Recommendation</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <?php if ($req['status'] === 'Pending'): ?>
                                    <?php $rec = recommend_assignment($req['requested_vehicle_type']); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($req['purpose']) ?></td>
                                        <td><?= htmlspecialchars($req['origin']) ?></td>
                                        <td><?= htmlspecialchars($req['destination']) ?></td>
                                        <td><?= htmlspecialchars($req['requested_vehicle_type']) ?></td>
                                        <td><?= htmlspecialchars($req['reservation_date'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($req['expected_return'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($req['status']) ?></td>
                                        <td>
                                            <?php if ($rec['vehicle'] && $rec['driver']): ?>
                                                <?= htmlspecialchars($rec['vehicle']['vehicle_name']) ?> / <?= htmlspecialchars($rec['driver']['driver_name']) ?>
                                            <?php else: ?>
                                                <span class="text-error">No available match</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="flex flex-col md:flex-row gap-3">
                                                <button class="btn btn-primary btn-sm" onclick="assign_modal_<?= $req['id'] ?>.showModal()">Assign</button>
                                                <a href="<?= htmlspecialchars($baseURL . '&remove_request=' . $req['id']) ?>" class="btn btn-error btn-sm" style="margin-left: 0;" onclick="return confirm('Reject this vehicle request?')">Reject</a>
                                            </div>
                                            <dialog id="assign_modal_<?= $req['id'] ?>" class="modal">
                                                <div class="modal-box">
                                                    <form method="dialog">
                                                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                    </form>
                                                    <h3 class="font-bold text-lg mb-4">Assign Vehicle & Driver</h3>
                                                    <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4">
                                                        <input type="hidden" name="approve_request" value="1">
                                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                        <div class="form-control">
                                                            <label class="label">Vehicle:</label>
                                                            <select name="vehicle_id" class="select select-bordered w-full" required>
                                                                <option value="">Select a vehicle</option>
                                                                <?php foreach ($vehicles as $veh): ?>
                                                                    <?php if ($veh['status'] === 'Active'): ?>
                                                                        <option value="<?= $veh['id'] ?>"><?= htmlspecialchars($veh['vehicle_name']) ?></option>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-control">
                                                            <label class="label">Driver:</label>
                                                            <select name="driver_id" class="select select-bordered w-full" required>
                                                                <option value="">Select a driver</option>
                                                                <?php foreach ($drivers as $drv): ?>
                                                                    <?php if ($drv['status'] === 'Available'): ?>
                                                                        <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['driver_name']) ?></option>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-success mt-4">Approve & Dispatch</button>
                                                    </form>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>close</button>
                                                </form>
                                            </dialog>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>


                <!-- Active & Past Dispatches Table -->

                <!-- Dispatch Log Modal -->
                <dialog id="dispatch_log_modal" class="modal">
                    <div class="modal-box w-11/12 max-w-5xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Dispatched Trips</h3>

                        <?php
                        // Pagination logic
                        $page = isset($_GET['dispatch_page']) ? max(1, intval($_GET['dispatch_page'])) : 1;
                        $perPage = 10;
                        $totalDispatches = count($dispatches);
                        $totalPages = ceil($totalDispatches / $perPage);
                        $start = ($page - 1) * $perPage;
                        $pagedDispatches = array_slice($dispatches, $start, $perPage);
                        ?>
                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>">
                            <div class="mb-2 flex gap-2">
                                <button type="submit" name="clear_dispatch_logs" class="btn btn-error btn-sm" onclick="return confirm('Clear all dispatch logs?')">Clear Log</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <!-- No batch select -->
                                            <th>Vehicle</th>
                                            <th>Driver</th>
                                            <th>Dispatch Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pagedDispatches as $d): ?>
                                            <tr>
                                                <!-- No batch select -->
                                                <td>
                                                    <?php
                                                    $vehName = '';
                                                    foreach ($vehicles as $veh) {
                                                        if ($veh['id'] == $d['vehicle_id']) {
                                                            $vehName = $veh['vehicle_name'];
                                                            break;
                                                        }
                                                    }
                                                    echo htmlspecialchars($vehName);
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $drvName = '';
                                                    foreach ($drivers as $drv) {
                                                        if ($drv['id'] == $d['driver_id']) {
                                                            $drvName = $drv['driver_name'];
                                                            break;
                                                        }
                                                    }
                                                    echo htmlspecialchars($drvName);
                                                    ?>
                                                </td>
                                                <td><?= htmlspecialchars($d['dispatch_date']) ?></td>
                                                <td><?= htmlspecialchars($d['status']) ?></td>
                                                <td>
                                                    <?php if ($d['status'] === 'Ongoing'): ?>
                                                        <a href="<?= htmlspecialchars($baseURL . '&complete=' . $d['id']) ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark this dispatch as completed?')">
                                                            <i data-lucide="check-circle" class="inline w-4 h-4"></i> Complete
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="<?= htmlspecialchars($baseURL . '&delete=' . $d['id']) ?>"
                                                        class="btn btn-sm btn-error"
                                                        onclick="return confirm('Delete this dispatch log?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        <!-- Pagination Controls -->
                        <div class="flex justify-center mt-4 gap-2">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a href="<?= htmlspecialchars($baseURL . '&dispatch_page=' . $p) ?>" class="btn btn-xs <?= $p == $page ? 'btn-primary' : 'btn-outline' ?>">Page <?= $p ?></a>
                            <?php endfor; ?>
                        </div>
                        <!-- No batch select JS -->
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            <?php endif; ?>
            </form>
            <!-- Leaflet.js & OSM/Nominatim Autocomplete JS & CSS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <!-- <link href="https://cdn.jsdelivr.net/npm/daisyui@4.0.0/dist/full.css" rel="stylesheet" type="text/css" /> -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const vehicles = <?php echo json_encode($vehicles); ?>;
                    const drivers = <?php echo json_encode($drivers); ?>;
                    const defaultLat = 14.65067;
                    const defaultLon = 121.04719;
                    const map = L.map('dispatchMap').setView([defaultLat, defaultLon], 14);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                    L.control.scale({
                        position: 'bottomleft',
                        metric: true,
                        imperial: true,
                        maxWidth: 200
                    }).addTo(map);

                    let markers = [];
                    let polylines = [];
                    let poiMarkers = [];
                    let pois = [];

                    function clearMap() {
                        markers.forEach(m => map.removeLayer(m));
                        polylines.forEach(l => map.removeLayer(l));
                        markers = [];
                        polylines = [];
                    }

                    function clearPOIMarkers() {
                        poiMarkers.forEach(m => map.removeLayer(m));
                        poiMarkers = [];
                    }

                    function addDispatchMarkers(dispatches) {
                        dispatches.forEach(function(d) {
                            const vehicle = vehicles.find(v => v.id == d.vehicle_id);
                            const driver = drivers.find(dr => dr.id == d.driver_id);
                            // Origin marker
                            const originMarker = L.marker([d.origin_lat, d.origin_lon], {
                                title: 'Origin'
                            }).addTo(map);
                            originMarker.bindPopup('<b>Vehicle:</b> ' + (vehicle ? vehicle.vehicle_name : d.vehicle_id) + '<br><b>Driver:</b> ' + (driver ? driver.driver_name : d.driver_id) + '<br><b>Origin:</b> ' + (d.origin || '-') + '<br><b>Destination:</b> ' + (d.destination || '-') + '<br><b>Status:</b> ' + d.status);
                            markers.push(originMarker);
                            // Destination marker
                            const destMarker = L.marker([d.destination_lat, d.destination_lon], {
                                title: 'Destination',
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                    iconAnchor: [12, 41],
                                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                })
                            }).addTo(map);
                            destMarker.bindPopup('<b>Destination</b><br>' + (d.destination || '-'));
                            markers.push(destMarker);
                            // Draw line between origin and destination
                            const poly = L.polyline([
                                [d.origin_lat, d.origin_lon],
                                [d.destination_lat, d.destination_lon]
                            ], {
                                color: 'blue',
                                weight: 3,
                                opacity: 0.7
                            }).addTo(map);
                            polylines.push(poly);
                        });
                    }

                    function addPOIMarkers(poisArr) {
                        poisArr.forEach(function(poi) {
                            let lat = typeof poi.lat === 'string' ? parseFloat(poi.lat) : poi.lat;
                            let lon = typeof poi.lon === 'string' ? parseFloat(poi.lon) : poi.lon;
                            if (!isNaN(lat) && !isNaN(lon)) {
                                const marker = L.marker([lat, lon], {
                                    title: poi.name,
                                    icon: L.icon({
                                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                        iconAnchor: [12, 41],
                                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                    })
                                }).addTo(map);
                                marker.bindPopup('<b>' + poi.name + '</b><br>' + (poi.description || ''));
                                poiMarkers.push(marker);
                            }
                        });
                    }

                    function fetchAndUpdateDispatches() {
                        fetch(window.location.pathname + '?ajax_ongoing_dispatches=1')
                            .then(res => res.json())
                            .then(data => {
                                clearMap();
                                addDispatchMarkers(data);
                            });
                    }

                    function fetchAndShowPOIs() {
                        fetch('js/custom_pois.json')
                            .then(res => res.json())
                            .then(data => {
                                pois = data;
                                clearPOIMarkers();
                                addPOIMarkers(pois);
                            });
                    }
                    // Add POI button logic
                    document.getElementById('addPoiBtn').onclick = function() {
                        // Prevent multiple listeners
                        if (window._poiMapClickHandler) {
                            map.off('click', window._poiMapClickHandler);
                        }
                        window._poiMapClickHandler = function(e) {
                            const lat = e.latlng.lat;
                            const lon = e.latlng.lng;
                            const name = prompt('Enter POI name:');
                            if (!name) {
                                map.off('click', window._poiMapClickHandler);
                                return;
                            }
                            const description = prompt('Enter POI description (optional):') || '';
                            fetch(window.location.pathname + '?add_custom_poi=1', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    name,
                                    lat,
                                    lon,
                                    description
                                })
                            }).then(res => res.json()).then(resp => {
                                if (resp.success) {
                                    fetchAndShowPOIs();
                                    alert('POI added!');
                                } else {
                                    alert('Failed to add POI.');
                                }
                                map.off('click', window._poiMapClickHandler);
                            }).catch(() => {
                                alert('Failed to add POI.');
                                map.off('click', window._poiMapClickHandler);
                            });
                        };
                        map.on('click', window._poiMapClickHandler);
                        alert('Click on the map to set POI location.');
                    };
                    // Search bar autocomplete
                    const searchInput = document.getElementById('mapSearch');
                    const suggestionsDiv = document.getElementById('searchSuggestions');
                    let searchTimeout = null;
                    searchInput.addEventListener('input', function() {
                        const query = searchInput.value.trim();
                        if (searchTimeout) clearTimeout(searchTimeout);
                        if (query.length < 3) {
                            suggestionsDiv.style.display = 'none';
                            return;
                        }
                        searchTimeout = setTimeout(() => {
                            fetch('https://corsproxy.io/?https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&q=' + encodeURIComponent(query))
                                .then(res => res.json())
                                .then(data => {
                                    suggestionsDiv.innerHTML = '';
                                    data.slice(0, 8).forEach(place => {
                                        const div = document.createElement('div');
                                        div.textContent = place.display_name;
                                        div.onclick = function() {
                                            searchInput.value = place.display_name;
                                            suggestionsDiv.style.display = 'none';
                                            map.setView([parseFloat(place.lat), parseFloat(place.lon)], 17);
                                        };
                                        suggestionsDiv.appendChild(div);
                                    });
                                    if (suggestionsDiv.innerHTML !== '') {
                                        suggestionsDiv.style.display = 'block';
                                    } else {
                                        suggestionsDiv.style.display = 'none';
                                    }
                                });
                        }, 300);
                    });
                    document.addEventListener('click', function(e) {
                        if (!suggestionsDiv.contains(e.target) && e.target !== searchInput) {
                            suggestionsDiv.style.display = 'none';
                        }
                    });
                    // Initial load
                    addDispatchMarkers(<?php echo json_encode(array_values($ongoingDispatches)); ?>);
                    fetchAndShowPOIs();
                    setInterval(fetchAndUpdateDispatches, 10000);
                });
            </script>
            <style>
                .osm-suggestions {
                    position: absolute;
                    /* z-index: 1000; */
                    background: #fff;
                    border: 1px solid #ccc;
                    max-height: 180px;
                    overflow-y: auto;
                    width: 100%;
                    display: none;
                }

                .osm-suggestions div {
                    padding: 8px;
                    cursor: pointer;
                }

                .osm-suggestions div:hover {
                    background: #f0f0f0;
                }

                .form-control {
                    position: relative;
                }

                .leaflet-container {
                    z-index: 0 !important;
                    /* push map behind UI elements */
                }
            </style>
            <script>
                let map, originMarker, destMarker, pois = [];
                let mapInitialized = false;

                function setupOSMAutocomplete(inputId, suggestionsId, markerType) {
                    const input = document.getElementById(inputId);
                    const suggestions = document.getElementById(suggestionsId);
                    if (!input || !suggestions) {
                        console.log('Autocomplete: input or suggestions element not found:', inputId, suggestionsId);
                        return;
                    }
                    // Remove previous event listeners by cloning
                    const newInput = input.cloneNode(true);
                    input.parentNode.replaceChild(newInput, input);
                    newInput.addEventListener('input', function() {
                        const query = newInput.value.trim().toLowerCase();
                        if (query.length < 3) {
                            suggestions.style.display = 'none';
                            return;
                        }
                        // Filter POIs first
                        let poiMatches = pois.filter(poi => poi.name.toLowerCase().includes(query));
                        suggestions.innerHTML = '';
                        poiMatches.forEach(poi => {
                            const div = document.createElement('div');
                            div.textContent = poi.name + ' (POI)';
                            div.style.fontWeight = 'bold';
                            div.onclick = function() {
                                newInput.value = poi.name;
                                suggestions.style.display = 'none';
                                // Place marker on map
                                if (map && poi.lat && poi.lon) {
                                    let lat = typeof poi.lat === 'string' ? parseFloat(poi.lat) : poi.lat;
                                    let lon = typeof poi.lon === 'string' ? parseFloat(poi.lon) : poi.lon;
                                    if (!isNaN(lat) && !isNaN(lon)) {
                                        const latlng = [lat, lon];
                                        if (markerType === 'origin') {
                                            if (originMarker) originMarker.remove();
                                            originMarker = L.marker(latlng, {
                                                title: 'Origin',
                                                icon: L.icon({
                                                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                                    iconAnchor: [12, 41]
                                                })
                                            }).addTo(map);
                                            map.setView(latlng, 13);
                                        } else if (markerType === 'destination') {
                                            if (destMarker) destMarker.remove();
                                            destMarker = L.marker(latlng, {
                                                title: 'Destination',
                                                icon: L.icon({
                                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                                    iconAnchor: [12, 41],
                                                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                                })
                                            }).addTo(map);
                                            map.setView(latlng, 13);
                                        }
                                    }
                                }
                            };
                            suggestions.appendChild(div);
                        });
                        // Then fetch Nominatim results
                        fetch('https://corsproxy.io/?https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&q=' + encodeURIComponent(query))
                            .then(res => res.json())
                            .then(data => {
                                data.slice(0, 5).forEach(place => {
                                    const div = document.createElement('div');
                                    div.textContent = place.display_name;
                                    div.onclick = function() {
                                        newInput.value = place.display_name;
                                        suggestions.style.display = 'none';
                                        // Place marker on map
                                        if (map && place.lat && place.lon) {
                                            const latlng = [parseFloat(place.lat), parseFloat(place.lon)];
                                            if (markerType === 'origin') {
                                                if (originMarker) originMarker.remove();
                                                originMarker = L.marker(latlng, {
                                                    title: 'Origin',
                                                    icon: L.icon({
                                                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                                        iconAnchor: [12, 41]
                                                    })
                                                }).addTo(map);
                                                map.setView(latlng, 13);
                                            } else if (markerType === 'destination') {
                                                if (destMarker) destMarker.remove();
                                                destMarker = L.marker(latlng, {
                                                    title: 'Destination',
                                                    icon: L.icon({
                                                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                                        iconAnchor: [12, 41],
                                                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                                    })
                                                }).addTo(map);
                                                map.setView(latlng, 13);
                                            }
                                        }
                                    };
                                    suggestions.appendChild(div);
                                });
                                if (suggestions.innerHTML !== '') {
                                    suggestions.style.display = 'block';
                                } else {
                                    suggestions.style.display = 'none';
                                }
                            });
                    });
                    document.addEventListener('click', function(e) {
                        if (!suggestions.contains(e.target) && e.target !== newInput) {
                            suggestions.style.display = 'none';
                        }
                    });
                    console.log('Autocomplete initialized for', inputId);
                }

                function initMapAndAutocomplete() {
                    if (mapInitialized) {
                        // Remove old map instance if exists
                        if (map) {
                            map.remove();
                            document.getElementById('osm-map').innerHTML = "";
                        }
                    }
                    map = L.map('osm-map').setView([14.5995, 120.9842], 6); // Default: Philippines
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                    // Load custom POIs from JSON file
                    fetch('js/custom_pois.json')
                        .then(res => res.json())
                        .then(data => {
                            pois = data;
                            // Show POIs on map
                            pois.forEach(function(poi) {
                                let lat = typeof poi.lat === 'string' ? parseFloat(poi.lat) : poi.lat;
                                let lon = typeof poi.lon === 'string' ? parseFloat(poi.lon) : poi.lon;
                                if (!isNaN(lat) && !isNaN(lon)) {
                                    const marker = L.marker([lat, lon], {
                                        title: poi.name,
                                        icon: L.icon({
                                            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                            iconAnchor: [12, 41],
                                            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                        })
                                    }).addTo(map);
                                    marker.bindPopup('<b>' + poi.name + '</b><br>' + (poi.description || ''));
                                }
                            });
                            // Setup autocomplete after POIs are loaded
                            setupOSMAutocomplete('origin', 'origin-suggestions', 'origin');
                            setupOSMAutocomplete('destination', 'destination-suggestions', 'destination');
                        });
                    mapInitialized = true;
                }

                // Re-initialize map and autocomplete every time the modal is opened (works for showModal and open attribute)
                document.addEventListener('DOMContentLoaded', function() {
                    const reqModal = document.getElementById('request_modal');
                    if (reqModal) {
                        // Use MutationObserver to detect when modal is opened
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (reqModal.hasAttribute('open')) {
                                    setTimeout(initMapAndAutocomplete, 100); // Delay to ensure DOM is ready
                                }
                            });
                        });
                        observer.observe(reqModal, {
                            attributes: true,
                            attributeFilter: ['open']
                        });
                    }
                });
            </script>
        </div>
    <?php
    // (AJAX endpoints moved to top of file to prevent accidental HTML output)
}
