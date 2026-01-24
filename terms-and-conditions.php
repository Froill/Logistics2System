<?php
session_start();

// Redirect to dashboard if already accepted T&C
if (isset($_SESSION['user_id']) && isset($_SESSION['t_and_c_accepted']) && $_SESSION['t_and_c_accepted']) {
    header("Location: dashboard.php");
    exit();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// Get role-specific T&C content
function getTermsContent($role)
{
    $terms = [

        /* ===================== SUPER ADMIN ===================== */
        'superadmin' => [
            'title' => 'Super Administrator Terms and Conditions',
            'content' => '
  <h3>1. Role and Legal Accountability</h3>
  <p>
    Super Administrator access grants full control over the Soliera Logistics 2 subsystem.
    This role carries a high level of trust and responsibility for protecting system integrity
    and personal data processed within the platform.
  </p>

  <h3>2. Data Privacy Compliance</h3>
  <ul>
    <li>All personal data processed in the system is protected under
      <strong>Republic Act No. 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>You are expected to follow the principles of confidentiality, integrity, and availability
      as defined by the Data Privacy Act and its Implementing Rules and Regulations.</li>
    <li>Unauthorized disclosure or misuse of personal data may result in administrative, civil,
      or criminal liability, subject to due process.</li>
  </ul>

  <h3>3. System Security and Cybercrime Law</h3>
  <ul>
    <li>All system activities must comply with
      <strong>Republic Act No. 10175 (Cybercrime Prevention Act of 2012)</strong>.</li>
    <li>Unauthorized access, system interference, or data manipulation is prohibited.</li>
  </ul>

  <h3>4. Logging, Monitoring, and Audit</h3>
  <ul>
    <li>System activities are logged for security, audit, and compliance purposes.</li>
    <li>Logs may be reviewed by authorized personnel in accordance with applicable laws.</li>
  </ul>
  '
        ],

        /* ===================== ADMIN ===================== */
        'admin' => [
            'title' => 'Administrator Terms and Conditions',
            'content' => '
  <h3>1. Authorized Administrative Access</h3>
  <p>
    Administrator access is granted solely for approved system management and operational support.
  </p>

  <h3>2. Data Privacy Obligations</h3>
  <ul>
    <li>All personal data is protected under <strong>RA 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>Access to personal or sensitive information must be limited to what is necessary
      for authorized tasks.</li>
  </ul>

  <h3>3. Cybersecurity Compliance</h3>
  <ul>
    <li>All actions must comply with <strong>RA 10175 (Cybercrime Prevention Act of 2012)</strong>.</li>
    <li>Unauthorized modification, access, or interference with system data is prohibited.</li>
  </ul>

  <h3>4. Audit and Accountability</h3>
  <ul>
    <li>Administrative actions are logged and subject to review.</li>
  </ul>
  '
        ],

        /* ===================== MANAGER ===================== */
        'manager' => [
            'title' => 'Manager Terms and Conditions',
            'content' => '
  <h3>1. Lawful Use of the System</h3>
  <p>
    Managerial access is provided for authorized oversight and decision-making functions only.
  </p>

  <h3>2. Confidentiality and Data Privacy</h3>
  <ul>
    <li>Personal and operational data accessed through the system is protected under
      <strong>RA 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>Unauthorized sharing or disclosure of data is prohibited.</li>
  </ul>

  <h3>3. Monitoring and Accountability</h3>
  <ul>
    <li>System activities are logged for security and audit purposes.</li>
  </ul>
  '
        ],

        /* ===================== SUPERVISOR ===================== */
        'supervisor' => [
            'title' => 'Supervisor Terms and Conditions',
            'content' => '
  <h3>1. Authorized Supervisory Use</h3>
  <p>
    Supervisory access is limited to approved review and authorization functions.
  </p>

  <h3>2. Data Protection</h3>
  <ul>
    <li>All accessed data is protected under <strong>RA 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>Improper use or disclosure of information is not allowed.</li>
  </ul>

  <h3>3. Logging and Review</h3>
  <ul>
    <li>All actions performed in the system are logged and may be reviewed.</li>
  </ul>
  '
        ],

        /* ===================== STAFF ===================== */
        'staff' => [
            'title' => 'Staff Terms and Conditions',
            'content' => '
  <h3>1. Limited Authorized Access</h3>
  <p>
    Staff access is restricted to assigned tasks and approved system functions only.
  </p>

  <h3>2. Accuracy of Information</h3>
  <ul>
    <li>Information entered into the system must be accurate and truthful.</li>
    <li>Intentional falsification of records may result in administrative action
      and may have legal implications under applicable laws.</li>
  </ul>

  <h3>3. Data Privacy and Security</h3>
  <ul>
    <li>All personal data is protected under <strong>RA 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>Unauthorized access to accounts or data may violate <strong>RA 10175 (Cybercrime Prevention Act of 2012)</strong>.</li>
  </ul>
  '
        ],

        /* ===================== DRIVER ===================== */
        'driver' => [
            'title' => 'Driver Terms and Conditions',
            'content' => '
  <h3>1. Scope of System Use</h3>
  <p>
    Driver access is limited to trip-related and assigned system functions.
  </p>
  <p>Comply with RA 4136 (Land Transportation and Traffic Code).</p>

  <h3>2. Accuracy of Records</h3>
  <ul>
    <li>Trip details and system entries must be accurate and complete.</li>
  </ul>

  <h3>3. Data Privacy and Cybersecurity</h3>
  <ul>
    <li>Personal data is protected under <strong>RA 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>Unauthorized system access may be subject to <strong>RA 10175 (Cybercrime Prevention Act of 2012)</strong>.</li>
  </ul>
  '
        ],

        /* ===================== REQUESTER ===================== */
        'requester' => [
            'title' => 'Requester Terms and Conditions',
            'content' => '
  <h3>1. Authorized Request Submission</h3>
  <p>
    Requester access is provided solely for submitting legitimate and authorized requests.
  </p>

  <h3>2. Accuracy and Responsibility</h3>
  <ul>
    <li>All submitted information must be accurate and truthful.</li>
    <li>Misrepresentation may result in administrative action.</li>
  </ul>

  <h3>3. Data Privacy</h3>
  <ul>
    <li>Personal data is protected under <strong>RA 10173 (Data Privacy Act of 2012)</strong>.</li>
    <li>System misuse may be subject to applicable laws.</li>
  </ul>
  '
        ],

    ];


    return $terms[$role] ?? $terms['default'];
}

$termsContent = getTermsContent($role);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($termsContent['title']); ?> - Logistics 2</title>
    <link rel="icon" type="image/x-icon" href="images/logo/sonly-2.png">
    <link rel="stylesheet" href="./css/theme.css">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
    <div class="min-h-screen bg-[url('./images/hotel3.jpg')] bg-blend-darken bg-cover bg-center flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-lg shadow-xl">
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#001f54] to-blue-900 text-[#F7B32B] p-6 sm:p-8 rounded-t-lg">
                <div class="flex items-center gap-3 mb-2 ">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h1 class="text-2xl sm:text-3xl font-bold"><?php echo htmlspecialchars($termsContent['title']); ?></h1>
                </div>
                <p class=" text-sm">Please review and accept our terms to continue</p>
            </div>

            <!-- Content -->
            <div class="p-6 sm:p-8">
                <div class="h-[260px] overflow-y-auto bg-gray-50 border border-gray-200 rounded-lg p-4 sm:p-6 mb-6">
                    <p class="text-gray-700 mb-4 leading-relaxed">
                        Welcome, <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($full_name); ?></span>.
                        Please carefully review the following terms and conditions before proceeding.
                    </p>
                    <div class="text-gray-700 leading-relaxed">
                        <?php echo $termsContent['content']; ?>
                    </div>
                    <p class="mt-4 text-gray-600 text-sm italic">
                        These terms and conditions are effective immediately and must be accepted to use the logistics system.
                    </p>
                </div>

                <!-- Agreement Form -->
                <form id="tnc-form" method="POST" action="includes/handle_tnc_acceptance.php" class="space-y-4">
                    <!-- Checkbox -->
                    <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <input
                            type="checkbox"
                            id="accept_tnc"
                            name="accept_tnc"
                            value="1"
                            class="checkbox border-gray-400 checked:bg-[#001f54] checked:text-[#F7B32B] mt-1"
                            required>
                        <label for="accept_tnc" class="text-gray-700 cursor-pointer leading-relaxed">
                            I have read and fully understand the terms and conditions listed above.
                            I acknowledge that I must comply with all stated requirements and policies.
                            I agree to abide by these terms throughout my use of the logistics system.
                        </label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-4">
                        <a href="logout.php" class="flex-1 btn btn-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Decline & Logout
                        </a>
                        <button
                            type="submit"
                            id="agree-btn"
                            class="flex-1 btn btn-success disabled"
                            disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            I Agree & Continue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const checkbox = document.getElementById('accept_tnc');
        const agreeBtn = document.getElementById('agree-btn');
        const form = document.getElementById('tnc-form');

        // Enable/disable button based on checkbox
        checkbox.addEventListener('change', function() {
            agreeBtn.disabled = !this.checked;
            if (this.checked) {
                agreeBtn.classList.remove('disabled');
            } else {
                agreeBtn.classList.add('disabled');
            }
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            if (!checkbox.checked) {
                e.preventDefault();
                alert('Please check the agreement checkbox to continue.');
            }
        });
    </script>
</body>

</html>