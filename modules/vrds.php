<?php

// VEHICLE RESERVATION AND DISPATCH SYSTEM (VRDS)

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/../includes/modules_logic.php';


function vrds_view($baseURL) {

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

?>



    <div>
        <h2 class="text-lg md:text-2xl font-bold mb-4">Vehicle Reservation & Dispatch</h2>
        <!-- Vehicle Request Form (Step 1) -->
        <div class="flex flex-col gap-2">
            <div class="flex gap-2 flex-wrap">
            <button class="btn btn-primary w-max" onclick="request_modal.showModal()">
                <i data-lucide="plus-circle" class="w-4 h-4 mr-1"></i> Request Vehicle
            </button>
            <button class="btn btn-secondary w-max" onclick="dispatch_log_modal.showModal()">
                <i data-lucide="list" class="w-4 h-4 mr-1"></i> View Dispatch Log
            </button>
            </div>
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
            </form>
            <!-- Leaflet.js & OSM/Nominatim Autocomplete JS & CSS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <style>
                .osm-suggestions {
                    position: absolute;
                    z-index: 1000;
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
                                    const latlng = [parseFloat(poi.lat), parseFloat(poi.lon)];
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
                                const marker = L.marker([poi.lat, poi.lon], {
                                    title: poi.name,
                                    icon: L.icon({
                                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                        iconAnchor: [12, 41],
                                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                    })
                                }).addTo(map);
                                marker.bindPopup('<b>' + poi.name + '</b><br>' + poi.description);
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
}