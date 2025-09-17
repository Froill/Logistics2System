<?php
// requester_history.php
// Shows vehicle requests for the current logged-in user (requester).
// - Uses mysqli only
// - Minimal/essential fields for requesters
// - Tailwind + daisyUI for styling
// - Pagination for performance
// - Prepared statements, error handling, edge cases handled


// --- SESSION / AUTH CHECK ---
// Ensure user is logged in and has a user id in session

$requester_id = (int) $_SESSION['user_id']; // cast to int for safety

$baseURL = 'dashboard.php?module=vrds'; // Adjust as needed for your setup

// --- DB CONNECTION ---
// Use your existing db.php connection. It must set $conn to a mysqli instance.
include 'db.php';

// Basic defensive: ensure $conn exists and is a mysqli
// if (!isset($conn) || !($conn instanceof mysqli)) {
//     // Fatal error — better to show friendly message and log the detail server-side
//     error_log('DB connection ($conn) is not available or not a mysqli instance in requester_history.php');
//     echo '<div class="p-4">Database connection error. Please try again later.</div>';
//     exit;
// }

// --- Pagination / Performance ---
// We'll show 10 requests per page by default.
// Using LIMIT + OFFSET prevents loading everything at once for users with many requests.
$per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// --- Security/UX: Sanitize GET params that we'll re-output (like page) ---
$escaped_page = htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8');

// --- Error reporting for mysqli: use exceptions optionally ---
// If your environment supports mysqli exceptions you can enable them. Comment/uncomment as needed.
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // --- Count total rows for pagination ---
    $count_sql = "SELECT COUNT(*) AS total FROM vehicle_requests WHERE requester_id = ?";
    if (!$stmt_count = $conn->prepare($count_sql)) {
        throw new Exception('Failed to prepare count statement: ' . $conn->error);
    }
    $stmt_count->bind_param('i', $requester_id);
    $stmt_count->execute();
    $stmt_count->bind_result($total_requests);
    $stmt_count->fetch();
    $stmt_count->close();

    $total_requests = (int) ($total_requests ?? 0);
    $total_pages = (int) ceil($total_requests / $per_page);

    // If asked page exceeds total pages, clamp it (edge case)
    if ($total_pages > 0 && $page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
        $escaped_page = htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8');
    }

    // --- Select essential columns only (for requesters) ---
    // We intentionally exclude internal admin-only fields such as approved_by, requested_driver_id unless needed.
    // Essential fields:
    // id, request_date, reservation_date, expected_return, purpose (truncated), origin, destination,
    // requested_vehicle_type, status, notes (truncated), dispatched_at/completed_at (optional small indicator)
    $sql = "
        SELECT
            id,
            request_date,
            reservation_date,
            expected_return,
            purpose,
            origin,
            destination,
            requested_vehicle_type,
            status,
            dispatched_at,
            completed_at,
            notes
        FROM vehicle_requests
        WHERE requester_id = ?
        ORDER BY request_date DESC
        LIMIT ? OFFSET ?
    ";

    if (!$stmt = $conn->prepare($sql)) {
        throw new Exception('Failed to prepare select statement: ' . $conn->error);
    }

    // 'i' for requester_id, 'i' for limit, 'i' for offset
    $stmt->bind_param('iii', $requester_id, $per_page, $offset);
    $stmt->execute();

    // Use bind_result to avoid relying on mysqlnd/get_result availability
    $stmt->bind_result(
        $id,
        $request_date,
        $reservation_date,
        $expected_return,
        $purpose,
        $origin,
        $destination,
        $requested_vehicle_type,
        $status,
        $dispatched_at,
        $completed_at,
        $notes
    );

?>

    <section class="mt-5">
        <div class="mb-4">
            <h2 class="text-lg md:text-2xl font-bold mb-2">My Vehicle Requests</h2>
            <p class="text-sm opacity-75">Showing requests you submitted</p>
        </div>

        <?php if ($total_requests === 0): ?>
            <!-- No requests -->
            <div class="card bg-base-200 p-6">
                <div class="flex items-center gap-4">
                    <div class="text-2xl">📭</div>
                    <div>
                        <div class="font-medium">No requests found</div>
                        <div class="text-sm opacity-75">You haven't submitted any vehicle requests yet.</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- List of requests -->
            <div class="space-y-3 flex flex-col ">
                <?php
                // Iterate rows and render compact cards for each request.
                // We purposely display truncated text for purpose/notes to keep the UI concise for requesters.
                while ($stmt->fetch()):
                    // Defensive: handle nulls and formatting
                    $display_request_date = $request_date ? date('Y-m-d H:i', strtotime($request_date)) : '-';
                    $display_reservation_date = $reservation_date ? date('Y-m-d', strtotime($reservation_date)) : '-';
                    $display_expected_return = $expected_return ? date('Y-m-d', strtotime($expected_return)) : '-';
                    $display_status = $status ?? 'Pending';

                    // Truncate helper (PHP) — keep to ~120 chars for purpose and 100 for notes
                    $truncate = function ($text, $len = 120) {
                        if ($text === null) return '-';
                        $text = trim($text);
                        if ($text === '') return '-';
                        if (mb_strlen($text) <= $len) return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                        return htmlspecialchars(mb_substr($text, 0, $len), ENT_QUOTES, 'UTF-8') . '...';
                    };

                    $t_purpose = $truncate($purpose, 120);
                    $t_notes = $truncate($notes, 100);

                    // Escape other fields
                    $esc_origin = $origin ? htmlspecialchars($origin, ENT_QUOTES, 'UTF-8') : '-';
                    $esc_destination = $destination ? htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') : '-';
                    $esc_vehicle_type = $requested_vehicle_type ? htmlspecialchars($requested_vehicle_type, ENT_QUOTES, 'UTF-8') : '-';

                    // Small status badge classes (daisyUI badges) — minimal risk of inline style
                    $status_badge_class = match (strtolower($display_status)) {
                        'approved' => 'badge badge-success',
                        'denied' => 'badge badge-error',
                        'dispatched' => 'badge badge-info',
                        'completed' => 'badge badge-ghost',
                        default => 'badge badge-outline',
                    };
                ?>
                    <div class="card bg-base-900 shadow-sm p-4">
                        <div class="flex justify-between flex-wrap mb-1">
                            <div>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-sm font-semibold">Request #<?php echo (int)$id; ?></h3>
                                    <span class="font-semi-bold <?php echo $status_badge_class; ?>"><?php echo htmlspecialchars($display_status, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="text-sm opacity-75 mt-1">Requested: <?php echo htmlspecialchars($display_request_date, ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>

                            <div class="text-right text-sm opacity-75">
                                <div>Reservation: <?php echo htmlspecialchars($display_reservation_date, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div>Return: <?php echo htmlspecialchars($display_expected_return, ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div>
                                <div class="text-sm opacity-75">Purpose</div>
                                <div class="font-medium"><?php echo $t_purpose; ?></div>
                            </div>

                            <div>
                                <div class="text-sm opacity-75">From → To</div>
                                <div class="font-medium">
                                    <?php echo $esc_origin; ?> → <?php echo $esc_destination; ?>
                                </div>
                            </div>

                            <div>
                                <div class="text-sm opacity-75">Requested Vehicle</div>
                                <div class="font-medium"><?php echo $esc_vehicle_type; ?></div>
                            </div>
                        </div>

                        <?php if ($notes && trim($notes) !== ''): ?>
                            <div class="mt-3 text-sm opacity-75">
                                <div class="text-sm opacity-75">Notes</div>
                                <div class=""><?php echo $t_notes; ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 flex items-center justify-between text-sm">
                            <div class="flex gap-2">
                                <?php if ($dispatched_at): ?>
                                    <span class="text-green-600">Dispatched: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($dispatched_at)), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>

                                <?php if ($completed_at): ?>
                                    <span class="text-gray-600">Completed: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($completed_at)), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; // end fetch loop 
                ?>
            </div>

            <!-- Pagination controls -->
            <div class="mt-6 flex flex-wrap gap-4 items-center justify-center md:justify-between">
                <div class="text-sm opacity-75">
                    Showing page <?php echo $escaped_page; ?> of <?php echo max(1, (int)$total_pages); ?> — <?php echo $total_requests; ?> request(s)
                </div>

                <div class="btn-group">
                    <!-- Prev -->
                    <a class="btn btn-sm <?php echo ($page <= 1) ? 'btn-disabled' : 'btn-outline'; ?>"
                        href="<?php echo $baseURL . '&page=' . max(1, $page - 1); ?>">Prev</a>
                    <!-- Simple numeric near current page for small UI footprint -->
                    <span class="btn btn-sm"><?php echo $page; ?>/<?php echo max(1, $total_pages); ?></span>

                    <!-- Next -->
                    <a class="btn btn-sm <?php echo ($page >= $total_pages) ? 'btn-disabled' : 'btn-outline'; ?>"
                        href="<?php echo $baseURL . '&page=' . min($total_pages, $page + 1); ?>">Next</a>
                </div>
            </div>
        <?php endif; // end if total_requests === 0 
        ?>

    </section>

<?php
    // --- Cleanup ---
    $stmt->close();
} catch (Exception $e) {
    // Generic, friendly error message for user; log the real error for devs
    error_log('Error in requester_history.php: ' . $e->getMessage());
    echo '<div class="p-4">An error occurred while loading your requests. Please try again later.</div>';
}
?>