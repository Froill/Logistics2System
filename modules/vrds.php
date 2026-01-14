<?php

// VEHICLE RESERVATION AND DISPATCH SYSTEM (VRDS)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/vrds_logic.php';
require_once __DIR__ . '/../includes/ajax.php';

// Handle request rejection BEFORE any output
if (isset($_POST['reject_request']) && isset($_POST['request_id'])) {
    $rid = intval($_POST['request_id']);
    global $conn;
    $stmt = $conn->prepare("UPDATE vehicle_requests SET status='Denied' WHERE id=?");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

function vrds_view($baseURL)
{
    // Add log to the current module that is being accessed by the user
    $moduleName = 'vrds';

    if ($_SESSION['current_module'] !== $moduleName) {
        log_audit_event(
            'VRDS',
            'ACCESS',
            null,
            $_SESSION['full_name'],
            'User accessed Vehicle Reservation & Dispatch module'
        );
        $_SESSION['current_module'] = $moduleName;
    }

    $role = $_SESSION['role'];

    vrds_logic($baseURL);
    $requests = fetchAll('vehicle_requests');
    $dispatches = fetchAll('dispatches');
    $vehicles = fetchAll('fleet_vehicles');
    $drivers = fetchAll('drivers');

    // Driver linkage: find driver record by session eid (drivers.eid)
    $currentUserEid = $_SESSION['eid'] ?? null;
    $isDriverUser = ($role === 'driver');
    $driverRecordId = null;
    if ($isDriverUser && $currentUserEid) {
        if ($stmt = $conn->prepare('SELECT id FROM drivers WHERE eid = ? LIMIT 1')) {
            $stmt->bind_param('s', $currentUserEid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $driverRecordId = (int)$r['id'];
            }
            $stmt->close();
        }
    }

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
        <!--!--Dispatched Trips block-->
        <div class="mb-6">
            <?php if (!in_array($role, ['requester', 'user'])): ?>
                <h3 class="text-lg font-bold mb-2">Dispatched Trips Map</h3>
            <?php endif; ?>
                <?php if (!in_array($role, ['requester', 'driver'])): ?>
                <div class="flex flex-wrap gap-2 mb-2">
                 <!-- Dispatched Trips mapsearch bar -->
                    <input id="mapSearch" class="input input-bordered" style="min-width:220px;max-width:350px;" placeholder="Search a place.." autocomplete="off">
                    <div id="searchSuggestions" class="osm-suggestions" style="position:absolute;z-index:1000;"></div>
                </div>
                <?php endif; ?>
            <div class="flex flex-wrap gap-2 mb-2">
                <?php if (!in_array($role, ['requester', 'driver'])): ?>
                <button id="addPoiBtn" class="btn btn-sm btn-success" type="button"><i data-lucide="map-pin-plus"></i> Add a POI </button>
                <button id="myPoisBtn" class="btn btn-sm btn-info" type="button"><i data-lucide="list"></i> POIs List</button>
                <button id="deletePoiBtn" class="btn btn-sm btn-error" type="button"><i data-lucide="trash-2"></i> Delete POI</button>
                <?php endif; ?>
                <!-- My POIs Modal -->
                <dialog id="myPoisModal" class="modal">
                    <div class="modal-box">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Points of Interest (POIs)</h3>
                        <div id="myPoiListContainer">
                            <div>Loading POIs...</div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
                <!-- Delete POI Modal -->
                <dialog id="deletePoiModal" class="modal">
                    <div class="modal-box">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Delete a Point of Interest (POI)</h3>
                        <div id="poiListContainer">
                            <div>Loading POIs...</div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
                <script src="js/delete-poi-logic.js"></script>
            </div>
            <!-- END of dispatched trips block-->

            <!-- OSM Map for Ongoing Dispatched Trips + Ongoing List -->
            <div class="flex gap-4" style="align-items:flex-start;">
                <div id="dispatchMap" style="height: 400px; width: 70%; border-radius:10px;"></div>
                <div id="ongoingDispatchList" style="width: 30%; max-height:400px; overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:8px;">
                    <h4 class="font-semibold mb-2">Ongoing Dispatches</h4>
                    <div id="ongoingDispatchItems">Loading...</div>
                </div>
            </div>
        </div>

        <?php if (!in_array($role, ['requester', 'user'])): ?>
            <h2 class="text-lg md:text-2xl font-bold mb-4">Vehicle Reservation & Dispatch</h2>
        <?php endif; ?>

        <!-- Vehicle Request Form (Step 1) -->
        <div class="flex flex-col gap-2">

            <div class="flex gap-2 flex-wrap">
                <?php if (in_array($role, ['admin', 'requester', 'user'])): ?>
                    <button class="btn btn-primary w-max" onclick="request_modal.showModal()">
                        <i data-lucide="plus-circle" class="w-4 h-4 mr-1"></i> Request Vehicle
                    </button>
                <?php endif; ?>
                <?php if (!in_array($role, ['requester', 'user'])): ?>
                    <button class="btn btn-secondary w-max" onclick="dispatch_log_modal.showModal()">
                        <i data-lucide="list" class="w-4 h-4 mr-1"></i> Dispatched Trips
                    </button>
                <?php endif; ?>
            </div>
            <!-- Vehicle Request Modal -->
            <dialog id="request_modal" class="modal">
                <div class="modal-box">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                    </form>
                    <form id="requestVehicleForm" method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6">
                        <fieldset class="fieldset bg-base-200 border-base-300 rounded-box w-xs border p-4">
                        <legend class="fieldset-legend">Request a Vehicle</legend>
                        <input type="hidden" name="request_vehicle" value="1">
                        <div class="form-control mb-2">
                            <label class="label">Purpose</label>
                            <select id="purposeSelect" name="purpose" class="select select-bordered" required>
                                <option value="">Select purpose</option>
                                <option value="Delivery">Delivery</option>
                                <option value="Supplies Pickup">Supplies Pickup</option>
                                <option value="Guest Transport">Guest Transport</option>
                                <option value="Staff Transport">Staff Transport</option>
                                <option value="Maintenance / Repair Runs">Maintenance / Repair Runs</option>
                                <option value="Official Errands">Official Errands</option>
                                <option value="Event Logistics">Event Logistics</option>
                                <option value="Emergency Response">Emergency Response</option>
                                <option value="Waste Disposal / Return">Waste Disposal / Return</option>
                                <option value="Other">Other (Please Specify)</option>
                            </select>
                        </div>
                        <div id="purposeOtherWrap" class="form-control mb-2" style="display:none;">
                            <label class="label">Please specify purpose</label>
                            <input id="purposeOther" type="text" name="purpose_other" class="input input-bordered" placeholder="Please specify purpose">
                        </div>
                        <div class="form-control mb-2">
                            <label class="label">Origin</label>
                            <input type="text" name="origin" id="origin" class="input input-bordered" autocomplete="off" required>
                            <input type="hidden" name="origin_lat" id="origin_lat">
                            <input type="hidden" name="origin_lon" id="origin_lon">
                            <div id="origin-suggestions" class="osm-suggestions"></div>
                        </div>
                        <div class="form-control mb-2">
                            <label class="label">Destination</label>
                            <input type="text" name="destination" id="destination" class="input input-bordered" autocomplete="off" required>
                            <input type="hidden" name="destination_lat" id="destination_lat">
                            <input type="hidden" name="destination_lon" id="destination_lon">
                            <div id="destination-suggestions" class="osm-suggestions"></div>
                        </div>
                        <div class="form-control mb-2">
                            <label class="label">Requested Vehicle Type</label>
                            <select id="requestedVehicleSelect" name="requested_vehicle_type" class="select select-bordered" required>
                                <option value="">Select vehicle type</option>
                                <?php foreach ($vehicle_types as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                                <option value="Other">Other (Please Specify)</option>
                            </select>
                        </div>
                        <div id="requestedVehicleOtherWrap" class="form-control mb-2" style="display:none;">
                            <label class="label">Please specify vehicle type</label>
                            <input id="requestedVehicleOther" type="text" name="requested_vehicle_type_other" class="input input-bordered" placeholder="Please specify vehicle type">
                        </div>
                        <div class="form-control mb-2">
                            <label class="label">Reservation Date</label>
                            <input id="reservationDate" type="date" name="reservation_date" class="input input-bordered" required min="<?= (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d') ?>">
                        </div>
                        <div class="form-control mb-2">
                            <label class="label">Completion Date</label>
                            <input id="expectedReturn" type="date" name="expected_return" class="input input-bordered" required min="<?= (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d') ?>">
                        </div>
                            
                        <button class="btn btn-primary btn-outline mt-2 w-full">Submit Request</button>
                                </fieldset>
                    </form>
            </dialog>

            <script src="js/dynamic-field-val.js"></script>

            <!-- Pending Requests Table (For Transport Officer Approval) -->
            <h3 class="text-md md:text-xl font-bold mt-6 mb-2">Pending Vehicle Requests</h3>
            <div class="overflow-x-auto mb-6">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
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
                                <?php $rec = recommend_assignment($req['requested_vehicle_type']);
                                // If current user is a driver, only show pending requests where they are recommended
                                if ($isDriverUser) {
                                    $recommendedDriverId = $rec['driver']['id'] ?? null;
                                    if (!$recommendedDriverId || $recommendedDriverId != $driverRecordId) {
                                        continue;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= 'REQ' . str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= htmlspecialchars($req['purpose']) ?></td>
                                    <?php
                                        $fullOrigin = trim($req['origin'] ?? '');
                                        $originWords = preg_split('/\s+/', $fullOrigin);
                                        if (count($originWords) > 7) {
                                            $short = implode(' ', array_slice($originWords, 0, 7)) . '...';
                                            $originDisplay = '<span title="' . htmlspecialchars($fullOrigin) . '">' . htmlspecialchars($short) . '</span>';
                                        } else {
                                            $originDisplay = htmlspecialchars($fullOrigin);
                                        }
                                        $fullDest = trim($req['destination'] ?? '');
                                        $destWords = preg_split('/\s+/', $fullDest);
                                        if (count($destWords) > 7) {
                                            $shortD = implode(' ', array_slice($destWords, 0, 7)) . '...';
                                            $destDisplay = '<span title="' . htmlspecialchars($fullDest) . '">' . htmlspecialchars($shortD) . '</span>';
                                        } else {
                                            $destDisplay = htmlspecialchars($fullDest);
                                        }
                                    ?>
                                    <td><?= $originDisplay ?></td>
                                    <td><?= $destDisplay ?></td>
                                    <td><?= htmlspecialchars($req['requested_vehicle_type']) ?></td>
                                    <td><?= htmlspecialchars($req['reservation_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($req['expected_return'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $status = $req['status'];
                                        $badgeClass = 'badge p-3 text-nowrap';
                                        if ($status === 'Approved') {
                                            $badgeClass .= ' badge-success';
                                        } elseif ($status === 'Denied') {
                                            $badgeClass .= ' badge-error ';
                                        } elseif ($status === 'Pending') {
                                            $badgeClass .= ' badge-warning';
                                        } else {
                                            $badgeClass .= ' badge-info';
                                        }
                                        ?>
                                        <span class="<?= $badgeClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($rec['vehicle'] && $rec['driver']): ?>
                                            <?= htmlspecialchars($rec['vehicle']['vehicle_name']) ?> / <?= htmlspecialchars($rec['driver']['driver_name']) ?>
                                        <?php else: ?>
                                            <span class="text-error">No available match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <?php if ($req['status'] === 'Pending'): ?>
                                                <?php if (!$isDriverUser): ?>
                                                    <button class="btn btn-primary btn-sm mb-2" style="width:110%;" onclick="assign_modal_<?= $req['id'] ?>.showModal()">Assign</button>
                                                    <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" style="display:block">
                                                        <input type="hidden" name="reject_request" value="1">
                                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                        <button type="submit" class="btn btn-error btn-sm" style="width:110%;" onclick="return confirm('Reject this vehicle request?')">Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="text-sm opacity-75">Pending (recommended)</div>
                                                <?php endif; ?>
                                            <?php elseif ($req['status'] === 'Approved'): ?>
                                                <button class="btn btn-info btn-sm" onclick="view_modal_<?= $req['id'] ?>.showModal()">
                                                    <i data-lucide="eye" style="width:5%;" class="inline w-4 h-4"></i>View</button>
                                                <!-- Delete button removed per request -->
                                            <?php endif; ?>
                                            <!-- View Modal for Approved Request -->
                                            <?php if ($req['status'] === 'Approved'): ?>
                                                <dialog id="view_modal_<?= $req['id'] ?>" class="modal">
                                                    <div class="modal-box">
                                                        <form method="dialog">
                                                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                        </form>
                                                        <h3 class="font-bold text-lg mb-4">Approved Vehicle Request Details</h3>
                                                        <div class="flex flex-col gap-2">
                                                            <div><b>Purpose:</b> <?= htmlspecialchars($req['purpose']) ?></div>
                                                            <div><b>Origin:</b> <?= htmlspecialchars($req['origin']) ?></div>
                                                            <div><b>Destination:</b> <?= htmlspecialchars($req['destination']) ?></div>
                                                            <div><b>Requested Vehicle Type:</b> <?= htmlspecialchars($req['requested_vehicle_type']) ?></div>
                                                            <div><b>Reservation Date:</b> <?= htmlspecialchars($req['reservation_date'] ?? '') ?></div>
                                                            <div><b>Expected Return:</b> <?= htmlspecialchars($req['expected_return'] ?? '') ?></div>
                                                            <div><b>Status:</b> <?= htmlspecialchars($req['status']) ?></div>
                                                        </div>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>close</button>
                                                    </form>
                                                </dialog>
                                            <?php endif; ?>
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
                                                    <!-- Driver selection -->
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

            <!-- Rejected Requests Table -->
            <h3 class="text-md md:text-xl font-bold mt-6 mb-2">Vehicle Requests History</h3>
            <div class="overflow-x-auto mb-6">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Purpose</th>
                            <th>Origin</th>
                            <th>Destination</th>
                            <th>Requested Vehicle Type</th>
                            <th>Reservation Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <?php
                                if (!in_array($req['status'], ['Denied','Approved'])) continue;
                                // If driver user, only show history items relevant to them
                                if ($isDriverUser) {
                                    $rec = recommend_assignment($req['requested_vehicle_type']);
                                    $recommendedDriverId = $rec['driver']['id'] ?? null;
                                    $assignedDriverId = $req['driver_id'] ?? ($req['assigned_driver_id'] ?? null);
                                    if (!($recommendedDriverId == $driverRecordId || $assignedDriverId == $driverRecordId)) {
                                        continue;
                                    }
                                }
                            ?>
                                <?php
                                    $fullOrigin = trim($req['origin'] ?? '');
                                    $originWords = preg_split('/\s+/', $fullOrigin);
                                    if (count($originWords) > 7) {
                                        $short = implode(' ', array_slice($originWords, 0, 7)) . '...';
                                        $originDisplay = '<span title="' . htmlspecialchars($fullOrigin) . '">' . htmlspecialchars($short) . '</span>';
                                    } else {
                                        $originDisplay = htmlspecialchars($fullOrigin);
                                    }
                                    $fullDest = trim($req['destination'] ?? '');
                                    $destWords = preg_split('/\s+/', $fullDest);
                                    if (count($destWords) > 7) {
                                        $shortD = implode(' ', array_slice($destWords, 0, 7)) . '...';
                                        $destDisplay = '<span title="' . htmlspecialchars($fullDest) . '">' . htmlspecialchars($shortD) . '</span>';
                                    } else {
                                        $destDisplay = htmlspecialchars($fullDest);
                                    }
                                ?>
                                <tr>
                                    <td><?= 'REQ' . str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= htmlspecialchars($req['purpose']) ?></td>
                                    <td><?= $originDisplay ?></td>
                                    <td><?= $destDisplay ?></td>
                                    <td><?= htmlspecialchars($req['requested_vehicle_type']) ?></td>
                                    <td><?= htmlspecialchars($req['reservation_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($req['expected_return'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $status = $req['status'];
                                        $badgeClass = 'badge p-3 text-nowrap';
                                        if ($status === 'Approved') {
                                            $badgeClass .= ' badge-success';
                                        } elseif ($status === 'Denied') {
                                            $badgeClass .= ' badge-error ';
                                        } elseif ($status === 'Pending') {
                                            $badgeClass .= ' badge-warning';
                                        } else {
                                            $badgeClass .= ' badge-info';
                                        }
                                        ?>
                                        <span class="<?= $badgeClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm" style="width:120%;" onclick="document.getElementById('view_modal_<?= $req['id'] ?>').showModal()">View</button>
                                        <dialog id="view_modal_<?= $req['id'] ?>" class="modal">
                                            <div class="modal-box">
                                                <form method="dialog">
                                                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                </form>
                                                <h3 class="font-bold text-lg mb-4">Vehicle Request Details</h3>
                                                <div class="flex flex-col gap-2">
                                                    <div><b>Purpose:</b> <?= htmlspecialchars($req['purpose']) ?></div>
                                                    <div><b>Origin:</b> <?= htmlspecialchars($req['origin']) ?></div>
                                                    <div><b>Destination:</b> <?= htmlspecialchars($req['destination']) ?></div>
                                                    <div><b>Requested Vehicle Type:</b> <?= htmlspecialchars($req['requested_vehicle_type']) ?></div>
                                                    <div><b>Reservation Date:</b> <?= htmlspecialchars($req['reservation_date'] ?? '') ?></div>
                                                    <div><b>Expected Return:</b> <?= htmlspecialchars($req['expected_return'] ?? '') ?></div>
                                                    <div><b>Status:</b> <?= htmlspecialchars($req['status']) ?></div>
                                                </div>
                                            </div>
                                            <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                        </dialog>
                                    </td>
                                </tr>
                           
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dispatch Log Modal -->
            <dialog id="dispatch_log_modal" class="modal">
                <div class="modal-box">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                    </form>
                    <h3 class="font-bold text-lg mb-4">Dispatched Trips</h3>

                    <?php // Removed pagination: show all dispatches in modal ?>
                    <form method="POST" action="<?= htmlspecialchars($baseURL) ?>">
                        <!--
                        <div class="mb-2 flex gap-2">
                            <button type="submit" name="clear_dispatch_logs" class="btn btn-error btn-sm" onclick="return confirm('Clear all dispatch logs?')">Remove All Records</button>
                        </div>
                    -->
                        <div class="overflow-x-auto">
                            <table class="table table-zebra" style="min-width:700px;">
                                <thead>
                                    <tr>
                                        <!-- No batch select -->
                                        <th>Ref ID</th>
                                        <th>Vehicle</th>
                                        <th>Driver</th>
                                        <th>Dispatch Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dispatches as $d): ?>
                                        <tr>
                                            <?php if ($isDriverUser && isset($driverRecordId) && $d['driver_id'] != $driverRecordId) { continue; } ?>
                                            <td><?= 'DSP' . str_pad($d['id'], 5, '0', STR_PAD_LEFT) ?></td>
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
                                            <td>
                                                <?php
                                                $badgeClass = 'badge badge-soft p-3 text-nowrap';
                                                if ($d['status'] === 'Ongoing') {
                                                    $badgeClass .= ' badge-soft badge-warning';
                                                } elseif ($d['status'] === 'Completed') {
                                                    $badgeClass .= ' badge-soft badge-success';
                                                } elseif ($d['status'] === 'Cancelled') {
                                                    $badgeClass .= ' badge-soft badge-error ';
                                                } else {
                                                    $badgeClass .= ' badge-soft badge-info';
                                                }
                                                ?>
                                                <span class="<?= $badgeClass ?>">
                                                    <?= htmlspecialchars($d['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex flex-col md:flex-row">
                                                    <?php if ($d['status'] === 'Ongoing'): ?>
                                                        <a href="<?= htmlspecialchars($baseURL . '&complete=' . $d['id']) ?>" class="btn btn-md btn-success sm:btn-sm md:btn-md w-1/2" onclick="return confirm('Mark this dispatch as completed?')">
                                                            <i data-lucide="check-circle" style="width:110%;" class="inline"></i><p class="inline">Complete</p>
                                                        </a>
                                                    <?php endif; ?>
                                                    <!--
                                                    <a href="<?= htmlspecialchars($baseURL . '&delete=' . $d['id']) ?>"
                                                        class="btn btn-xs btn-error sm:btn-sm md:btn-md w-1/2"
                                                        onclick="return confirm('Delete this dispatch log?')">
                                                        <i data-lucide="delete" class="inline"></i>Delete
                                                    </a> -->
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
            
            <!-- Leaflet.js & OSM/Nominatim Autocomplete JS & CSS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <link href="https://cdn.jsdelivr.net/npm/daisyui@4.0.0/dist/full.css" rel="stylesheet" type="text/css" />
            <script>
                // OSM/Nominatim Autocomplete Logic
                document.addEventListener('DOMContentLoaded', function() {
                    const vehicles = <?php echo json_encode($vehicles); ?>;
                    const drivers = <?php echo json_encode($drivers); ?>;
                    const defaultLat = 14.65067;
                    const defaultLon = 121.04719;
                    const map = L.map('dispatchMap').setView([defaultLat, defaultLon], 13);
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
                    let originMarkersById = {};

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
                        originMarkersById = {};
                        dispatches.forEach(function(d) {
                            const vehicle = vehicles.find(v => v.id == d.vehicle_id);
                            const driver = drivers.find(dr => dr.id == d.driver_id);
                            // Parse coordinates as floats
                            const oLat = parseFloat(d.origin_lat);
                            const oLon = parseFloat(d.origin_lon);
                            const dLat = parseFloat(d.destination_lat);
                            const dLon = parseFloat(d.destination_lon);
                            // Only plot markers if coordinates are valid
                            if (!isNaN(oLat) && !isNaN(oLon)) {
                                const originMarker = L.marker([oLat, oLon], {
                                    title: 'Origin'
                                }).addTo(map);
                                originMarker.bindPopup('<b>Vehicle:</b> ' + (vehicle ? vehicle.vehicle_name : d.vehicle_id) + '<br><b>Driver:</b> ' + (driver ? driver.driver_name : d.driver_id) + '<br><b>Origin:</b> ' + (d.origin || '-') + '<br><b>Destination:</b> ' + (d.destination || '-') + '<br><b>Status:</b> ' + d.status);
                                originMarker._dispatchId = d.id;
                                originMarkersById[d.id] = originMarker;
                                markers.push(originMarker);
                            }
                            if (!isNaN(dLat) && !isNaN(dLon)) {
                                const destMarker = L.marker([dLat, dLon], {
                                    title: 'Destination',
                                    icon: L.icon({
                                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                        iconAnchor: [12, 41],
                                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
                                    })
                                }).addTo(map);
                                destMarker.bindPopup('<b>Destination</b><br>' + (d.destination || '-'));
                                markers.push(destMarker);
                            }
                            // Draw line only if all coordinates are valid
                            if (!isNaN(oLat) && !isNaN(oLon) && !isNaN(dLat) && !isNaN(dLon)) {
                                const poly = L.polyline([
                                    [oLat, oLon],
                                    [dLat, dLon]
                                ], {
                                    color: 'blue',
                                    weight: 10, // Increased from 3 to 10 for easier clicking
                                    opacity: 0.7
                                }).addTo(map);

                                // Calculate distance (km) using Haversine formula
                                function haversine(lat1, lon1, lat2, lon2) {
                                    const R = 6371; // Earth radius in km
                                    const toRad = deg => deg * Math.PI / 180;
                                    const dLat = toRad(lat2 - lat1);
                                    const dLon = toRad(lon2 - lon1);
                                    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                                        Math.sin(dLon / 2) * Math.sin(dLon / 2);
                                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                                    return R * c;
                                }
                                const distanceKm = haversine(oLat, oLon, dLat, dLon);
                                // Estimate ETA (hours) at average speed (e.g., 40 km/h)
                                const avgSpeed = 40; // km/h
                                const etaHours = distanceKm / avgSpeed;
                                const etaMinutes = Math.round(etaHours * 60);

                                // Popup details for dispatched trip
                                let popupHtml = '<b>Dispatched Trip Details</b><br><br>';
                                popupHtml += '<b>Vehicle:</b> ' + (vehicle ? vehicle.vehicle_name : d.vehicle_id) + '<br>';
                                popupHtml += '<b>Driver:</b> ' + (driver ? driver.driver_name : d.driver_id) + '<br>';
                                popupHtml += '<b>Origin:</b> ' + (d.origin || '-') + '<br>';
                                popupHtml += '<b>Destination:</b> ' + (d.destination || '-') + '<br>';
                                popupHtml += '<b>Status:</b> ' + (d.status || '-') + '<br>';
                                popupHtml += '<b>Distance:</b> ' + distanceKm.toFixed(2) + ' km<br>';
                                popupHtml += '<b>ETA:</b> ' + (etaMinutes > 0 ? etaMinutes + ' min' : 'N/A') + '<br>';
                                if (d.purpose) popupHtml += '<b>Purpose:</b> ' + d.purpose + '<br>';
                                if (d.dispatch_date) popupHtml += '<b>Dispatch Date:</b> ' + d.dispatch_date + '<br>';
                                poly.bindPopup(popupHtml);
                                polylines.push(poly);
                            }
                        });
                        // After adding markers, render the sidebar list
                        renderOngoingList(dispatches);
                    }

                    function renderOngoingList(dispatches){
                        const container = document.getElementById('ongoingDispatchItems');
                        if (!container) return;
                        if (!dispatches || dispatches.length === 0){ container.innerHTML = '<div class="opacity-50">No ongoing dispatches</div>'; return; }
                        container.innerHTML = '';
                        dispatches.forEach(d => {
                            const vehicle = vehicles.find(v => v.id == d.vehicle_id);
                            const driver = drivers.find(dr => dr.id == d.driver_id);
                            const el = document.createElement('div');
                            el.className = 'mb-2 p-2 border rounded hover:bg-gray-50 cursor-pointer';
                            el.style.display = 'flex';
                            el.style.flexDirection = 'column';
                            el.style.gap = '4px';
                            const title = document.createElement('div');
                            title.innerHTML = '<b>' + (vehicle ? vehicle.vehicle_name : 'Vehicle #' + d.vehicle_id) + '</b>';
                            const sub = document.createElement('div');
                            sub.style.fontSize = '0.9rem';
                            sub.style.opacity = '0.9';
                            sub.textContent = (driver ? driver.driver_name : 'Driver #' + d.driver_id) + ' — ' + (d.origin || '-') ;
                            el.appendChild(title);
                            el.appendChild(sub);
                            el.onclick = function(){
                                const m = originMarkersById[d.id];
                                if (m){
                                    const latlng = m.getLatLng();
                                    map.setView([latlng.lat, latlng.lng], 15);
                                    m.openPopup();
                                } else if (d.origin_lat && d.origin_lon) {
                                    map.setView([parseFloat(d.origin_lat), parseFloat(d.origin_lon)], 15);
                                }
                            };
                            container.appendChild(el);
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
                        // Add cache-busting query param to always get latest POIs
                        fetch('js/custom_pois.json?v=' + Date.now())
                            .then(res => res.json())
                            .then(data => {
                                pois = data;
                                clearPOIMarkers();
                                addPOIMarkers(pois);
                            });
                    }
                    // Add POI button logic
                    const _addPoiBtn = document.getElementById('addPoiBtn');
                    if (_addPoiBtn) {
                        _addPoiBtn.onclick = function() {
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
                    }

                    // Search bar autocomplete
                    const searchInput = document.getElementById('mapSearch');
                    const suggestionsDiv = document.getElementById('searchSuggestions');
                    let searchTimeout = null;
                    if (searchInput && suggestionsDiv) {
                    searchInput.addEventListener('input', function() {
                        const query = searchInput.value.trim().toLowerCase();
                        if (searchTimeout) clearTimeout(searchTimeout);
                        if (query.length < 3) {
                            suggestionsDiv.style.display = 'none';
                            return;
                        }
                        searchTimeout = setTimeout(() => {
                            suggestionsDiv.innerHTML = '';
                            // Suggest POIs first
                            let poiMatches = pois.filter(poi => poi.name && poi.name.toLowerCase().includes(query));
                            poiMatches.forEach(poi => {
                                const div = document.createElement('div');
                                div.textContent = poi.name + ' (POI)';
                                div.style.fontWeight = 'bold';
                                div.onclick = function() {
                                    searchInput.value = poi.name;
                                    suggestionsDiv.style.display = 'none';
                                    if (poi.lat && poi.lon) {
                                        let lat = typeof poi.lat === 'string' ? parseFloat(poi.lat) : poi.lat;
                                        let lon = typeof poi.lon === 'string' ? parseFloat(poi.lon) : poi.lon;
                                        if (!isNaN(lat) && !isNaN(lon)) {
                                            map.setView([lat, lon], 17);
                                        }
                                    }
                                };
                                suggestionsDiv.appendChild(div);
                            });
                            // Then fetch Nominatim results
                            fetch('https://corsproxy.io/?https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&q=' + encodeURIComponent(query))
                                .then(res => res.json())
                                .then(data => {
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
                    // Initial load, then periodic updates, 10000 = 10 seconds
                    addDispatchMarkers(<?php echo json_encode(array_values($ongoingDispatches)); ?>);
                    fetchAndShowPOIs();
                    setInterval(fetchAndUpdateDispatches, 10000);
}                });
            </script>
            <style>
                /* Responsive adjustments for VRDS page */
                .modal-box { box-sizing: border-box; max-width: 50vw !important; }
                dialog.modal { padding: 0.5rem; }
                .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                .table { width: 100%; min-width: 0; table-layout: auto; }
                .table th, .table td { white-space: normal; word-break: break-word; }

                /* Small screens: allow tables to scroll instead of overflowing */
                @media (max-width: 640px) {
                    .modal-box { padding: 0.5rem; }
                    .table { font-size: 0.9rem; }
                    .leaflet-container { max-width: 100%; }
                }
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
                // Autocomplete for 'origin' input in request vehicle modal (POIs + Nominatim)

                document.addEventListener('DOMContentLoaded', function() {
                    const originInput = document.getElementById('origin');
                    const originSuggestions = document.getElementById('origin-suggestions');
                    const originLat = document.getElementById('origin_lat');
                    const originLon = document.getElementById('origin_lon');
                    let searchTimeout = null;
                    // Helper to get POIs, always up-to-date
                    function getPois(callback) {
                        if (window.pois && Array.isArray(window.pois) && window.pois.length > 0) {
                            callback(window.pois);
                        } else {
                            fetch('js/custom_pois.json?v=' + Date.now())
                                .then(res => res.json())
                                .then(data => {
                                    window.pois = data;
                                    callback(window.pois);
                                })
                                .catch(() => callback([]));
                        }
                    }
                    if (originInput && originSuggestions && originLat && originLon) {
                        originInput.addEventListener('input', function() {
                            // Clear lat/lon if user types
                            originLat.value = '';
                            originLon.value = '';
                            const query = originInput.value.trim().toLowerCase();
                            if (searchTimeout) clearTimeout(searchTimeout);
                            if (query.length < 3) {
                                originSuggestions.style.display = 'none';
                                return;
                            }
                            searchTimeout = setTimeout(() => {
                                originSuggestions.innerHTML = '';
                                getPois(function(pois) {
                                    let poiMatches = pois.filter(poi => poi.name && poi.name.toLowerCase().includes(query));
                                    poiMatches.forEach(poi => {
                                        const div = document.createElement('div');
                                        div.textContent = poi.name + ' (POI)';
                                        div.style.fontWeight = 'bold';
                                        div.onclick = function() {
                                            originInput.value = poi.name;
                                            originLat.value = poi.lat;
                                            originLon.value = poi.lon;
                                            originSuggestions.style.display = 'none';
                                        };
                                        originSuggestions.appendChild(div);
                                    });
                                    // Then fetch Nominatim results
                                    fetch('https://corsproxy.io/?https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&q=' + encodeURIComponent(query))
                                        .then(res => res.json())
                                        .then(data => {
                                            data.slice(0, 8).forEach(place => {
                                                const div = document.createElement('div');
                                                div.textContent = place.display_name;
                                                div.onclick = function() {
                                                    originInput.value = place.display_name;
                                                    originLat.value = place.lat;
                                                    originLon.value = place.lon;
                                                    originSuggestions.style.display = 'none';
                                                };
                                                originSuggestions.appendChild(div);
                                            });
                                            if (originSuggestions.innerHTML !== '') {
                                                originSuggestions.style.display = 'block';
                                            } else {
                                                originSuggestions.style.display = 'none';
                                            }
                                        });
                                });
                            }, 300);
                        });
                        document.addEventListener('click', function(e) {
                            if (!originSuggestions.contains(e.target) && e.target !== originInput) {
                                originSuggestions.style.display = 'none';
                            }
                        });
                    }
                });

                document.addEventListener('DOMContentLoaded', function() {
                    const myPoisBtn = document.getElementById('myPoisBtn');
                    const myPoisModal = document.getElementById('myPoisModal');
                    const myPoiListContainer = document.getElementById('myPoiListContainer');

                    if (myPoisBtn && myPoisModal && myPoiListContainer) {
                        myPoisBtn.addEventListener('click', function() {
                            fetch('js/custom_pois.json?v=' + Date.now())
                                .then(res => res.json())
                                .then(data => {
                                    if (!Array.isArray(data) || data.length === 0) {
                                        myPoiListContainer.innerHTML = '<div>No POIs found.</div>';
                                        return;
                                    }
                                    let html = '<ul class="list-disc pl-4">';
                                    data.forEach((poi, idx) => {
                                        html += `<li class="flex items-center justify-between mb-2">
                                    <span><b>${poi.name}</b> (${poi.lat}, ${poi.lon})<br><small>${poi.description || ''}</small></span>
                                    <button class="btn btn-xs btn-primary" data-edit-idx="${idx}"><i data-lucide="edit"></i> Edit</button>
                                </li>`;
                                    });
                                    html += '</ul>';
                                    myPoiListContainer.innerHTML = html;
                                    // Attach edit handlers
                                    Array.from(myPoiListContainer.querySelectorAll('button[data-edit-idx]')).forEach(btn => {
                                        btn.onclick = function(e) {
                                            e.preventDefault();
                                            const idx = parseInt(btn.getAttribute('data-edit-idx'));
                                            const current = data[idx] || {};
                                            // Prompt for name first (pre-filled), then description
                                            const newName = prompt('Edit POI name:', current.name || '');
                                            if (newName === null) return; // user cancelled
                                            const newDesc = prompt('Edit POI description:', current.description || '');
                                            if (newDesc === null) return; // user cancelled
                                            current.name = newName;
                                            current.description = newDesc;
                                            // Send updated POI (including name & description) to backend
                                            fetch('includes/ajax.php?edit_custom_poi=1', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json'
                                                    },
                                                    body: JSON.stringify({
                                                        idx,
                                                        poi: current
                                                    })
                                                })
                                                .then(res => res.json())
                                                .then(resp => {
                                                    if (resp.success) {
                                                        alert('POI updated!');
                                                        btn.parentElement.querySelector('span').innerHTML = `<b>${current.name}</b> (${current.lat}, ${current.lon})<br><small>${current.description || ''}</small>`;
                                                        if (typeof fetchAndShowPOIs === 'function') fetchAndShowPOIs();
                                                    } else {
                                                        alert('Failed to update POI.');
                                                    }
                                                })
                                                .catch(() => alert('Failed to update POI.'));
                                        };
                                    });
                                })
                                .catch(() => {
                                    myPoiListContainer.innerHTML = '<div>Failed to load POIs.</div>';
                                });

                            // Show modal after initiating fetch (modal content will update when data arrives)
                            if (typeof myPoisModal.showModal === 'function') {
                                myPoisModal.showModal();
                            } else {
                                // Fallback for browsers without dialog support
                                myPoisModal.style.display = 'block';
                            }
                        });
                    }
                });
                // Autocomplete for 'destination' input in request vehicle modal (POIs + Nominatim)
                document.addEventListener('DOMContentLoaded', function() {
                    const destInput = document.getElementById('destination');
                    const destSuggestions = document.getElementById('destination-suggestions');
                    const destLat = document.getElementById('destination_lat');
                    const destLon = document.getElementById('destination_lon');
                    let searchTimeout = null;

                    function getPois(callback) {
                        if (window.pois && Array.isArray(window.pois) && window.pois.length > 0) {
                            callback(window.pois);
                        } else {
                            fetch('js/custom_pois.json?v=' + Date.now())
                                .then(res => res.json())
                                .then(data => {
                                    window.pois = data;
                                    callback(window.pois);
                                })
                                .catch(() => callback([]));
                        }
                    }
                    if (destInput && destSuggestions && destLat && destLon) {
                        destInput.addEventListener('input', function() {
                            // Clear lat/lon if user types
                            destLat.value = '';
                            destLon.value = '';
                            const query = destInput.value.trim().toLowerCase();
                            if (searchTimeout) clearTimeout(searchTimeout);
                            if (query.length < 3) {
                                destSuggestions.style.display = 'none';
                                return;
                            }
                            searchTimeout = setTimeout(() => {
                                destSuggestions.innerHTML = '';
                                getPois(function(pois) {
                                    let poiMatches = pois.filter(poi => poi.name && poi.name.toLowerCase().includes(query));
                                    poiMatches.forEach(poi => {
                                        const div = document.createElement('div');
                                        div.textContent = poi.name + ' (POI)';
                                        div.style.fontWeight = 'bold';
                                        div.onclick = function() {
                                            destInput.value = poi.name;
                                            destLat.value = poi.lat;
                                            destLon.value = poi.lon;
                                            destSuggestions.style.display = 'none';
                                        };
                                        destSuggestions.appendChild(div);
                                    });
                                    // Then fetch Nominatim results
                                    fetch('https://corsproxy.io/?https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&q=' + encodeURIComponent(query))
                                        .then(res => res.json())
                                        .then(data => {
                                            data.slice(0, 8).forEach(place => {
                                                const div = document.createElement('div');
                                                div.textContent = place.display_name;
                                                div.onclick = function() {
                                                    destInput.value = place.display_name;
                                                    destLat.value = place.lat;
                                                    destLon.value = place.lon;
                                                    destSuggestions.style.display = 'none';
                                                };
                                                destSuggestions.appendChild(div);
                                            });
                                            if (destSuggestions.innerHTML !== '') {
                                                destSuggestions.style.display = 'block';
                                            } else {
                                                destSuggestions.style.display = 'none';
                                            }
                                        });
                                });
                            }, 300);
                        });
                        document.addEventListener('click', function(e) {
                            if (!destSuggestions.contains(e.target) && e.target !== destInput) {
                                destSuggestions.style.display = 'none';
                            }
                        });
                    }
                });
            </script>

        </div>
    <?php
}
