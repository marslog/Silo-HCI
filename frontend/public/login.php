<?php
/**
 * Login Page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$config = require __DIR__ . '/../src/Config/config.php';
$assetVersion = $_ENV['ASSET_VERSION'] ?? '1.0.0';
$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Silo HCI</title>
    
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES); ?>">
    <link rel="stylesheet" href="/assets/css/theme.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES); ?>">
    <link rel="stylesheet" href="/assets/fonts/fontawesome.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES); ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(118,75,162,0.5) 0%, rgba(102,126,234,0.3) 35%, rgba(17,24,39,0.92) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 18px;
            position: relative;
            overflow: hidden;
        }
        
        body::before,
        body::after {
            content: '';
            position: absolute;
            z-index: 0;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.55;
        }
        
        body::before {
            width: 420px;
            height: 420px;
            background: rgba(118, 75, 162, 0.6);
            top: -120px;
            left: -120px;
        }
        
        body::after {
            width: 500px;
            height: 500px;
            background: rgba(102, 126, 234, 0.45);
            bottom: -180px;
            right: -160px;
        }
        
        .login-container {
            width: 100%;
            max-width: 548px;
            position: relative;
            z-index: 2;
        }
        
        .login-wrapper {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 16px 44px rgba(17, 24, 39, 0.32);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(12px);
            min-height: 300px;
        }
        
        .login-illustration {
            position: relative;
            background: linear-gradient(160deg, rgba(102,126,234,0.9) 0%, rgba(118,75,162,0.85) 55%, rgba(79,70,229,0.8) 100%);
            padding: 24px 22px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .login-illustration::before {
            content: '';
            position: absolute;
            inset: 20px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            opacity: 0.6;
        }
        
        .illustration-content {
            position: relative;
            z-index: 1;
        }
        
        .illustration-heading {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.36px;
        }

        .illustration-subtitle {
            font-size: 9.5px;
            line-height: 1.4;
            color: rgba(255,255,255,0.8);
            max-width: 210px;
        }
        
        .illustration-graphic {
            position: relative;
            margin-top: 18px;
            height: 150px;
        }
        
        .tower {
            position: absolute;
            bottom: 0;
            width: 68px;
            background: linear-gradient(180deg, rgba(255,255,255,0.85) 0%, rgba(255,255,255,0.6) 100%);
            border-radius: 20px 20px 0 0;
            box-shadow: 0 20px 40px rgba(15,23,42,0.3);
            overflow: hidden;
        }
        
        .tower::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(102,126,234,0.12) 0%, rgba(118,75,162,0.22) 100%);
        }
        
        .tower-step {
            position: absolute;
            left: 20px;
            width: 80px;
            height: 10px;
            background: rgba(102,126,234,0.2);
            border-radius: 4px;
        }
        
        .tower:nth-child(1) {
            left: 2px;
            height: 120px;
        }

        .tower:nth-child(2) {
            left: 90px;
            height: 145px;
        }

        .tower:nth-child(3) {
            left: 175px;
            height: 105px;
        }
        
        .tower:nth-child(1) .tower-step { bottom: 80px; }
        .tower:nth-child(2) .tower-step { bottom: 110px; }
        .tower:nth-child(3) .tower-step { bottom: 60px; }
        
        .connector {
            position: absolute;
            bottom: 32px;
            left: 46px;
            width: 120px;
            height: 34px;
            border-radius: 35px;
            background: linear-gradient(135deg, rgba(59,130,246,0.45) 0%, rgba(59,130,246,0.15) 100%);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
        }
        
        .illustration-footer {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            margin-top: 20px;
            gap: 7px;
        }
        
        .illustration-footer img {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(255,255,255,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        
        .illustration-footer span {
            font-size: 9px;
            color: rgba(255,255,255,0.75);
            line-height: 1.5;
        }
        
        .login-panel {
            background: rgba(255, 255, 255, 0.98);
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
        }
        
        .brand-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .brand-badge img {
            width: 120px;
            max-width: 100%;
        }
        
        .login-header {
            text-align: left;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            font-size: 17px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
            letter-spacing: -0.22px;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 9.5px;
            line-height: 1.3;
        }
        
        .login-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 9px;
            font-size: 9.5px;
        }
        
        .login-actions a {
            color: #6366f1;
            text-decoration: none;
        }
        
        .alert {
            margin-bottom: 18px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 11.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }
        
        .alert-error {
            background: rgba(254, 226, 226, 0.65);
            color: #b91c1c;
            border-color: rgba(248, 113, 113, 0.35);
        }
        
        .alert-success {
            background: rgba(220, 252, 231, 0.65);
            color: #15803d;
            border-color: rgba(134, 239, 172, 0.35);
        }
        
        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e5e7eb;
            border-radius: 7px;
            font-size: 11px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.92);
            color: #000000;
            font-weight: 500;
        }

        .form-group label {
            color: #000000;
            font-weight: 600;
            margin-bottom: 4px;
            display: inline-block;
        }

        .form-group input::placeholder {
            color: rgba(0, 0, 0, 0.7);
            font-weight: 400;
        }

        .form-group input:focus {
            border-color: rgba(102, 126, 234, 0.65);
            background: rgba(255,255,255,0.98);
            color: #000000;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.15);
        }
        
        .remember-me {
            margin: 9px 0 0;
        }
        
        .login-button {
            margin-top: 16px;
            border-radius: 7px;
            font-size: 11.5px;
        }
        
        .form-2fa.active {
            margin-top: 5px;
        }
        
        .otp-input {
            gap: 5px;
        }
        
        .otp-input input {
            width: 32px;
            height: 32px;
        }
        
        .login-footer {
            border-top: none;
            margin-top: auto;
            padding-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        
        .login-footer p {
            font-size: 8.5px;
            color: #9ca3af;
        }
        
        .vendor-credit {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #1f2937;
        }

        .vendor-credit img {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            background: #f3f4f6;
            padding: 4px;
        }

        .vendor-credit .vendor-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .vendor-credit .vendor-name {
            font-weight: 600;
            font-size: 9.5px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #1f2937;
        }

        .vendor-credit .vendor-tagline {
            font-size: 9px;
            color: #4b5563;
        }
        
        @media (max-width: 1024px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            
            .login-illustration {
                display: none;
            }
            
            .login-panel {
                padding: 30px 24px;
            }
            
            .brand-badge {
                justify-content: center;
                gap: 12px;
            }
            
            .login-footer {
                align-items: center;
                text-align: center;
            }
        }
        
        @media (max-width: 640px) {
            body {
                padding: 18px 10px;
            }
            
            .login-panel {
                padding: 30px 20px;
            }
            
            .brand-badge img {
                width: 120px;
            }

            .vendor-credit {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .vendor-credit img {
                width: 48px;
                height: 48px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-wrapper">
        <div class="login-illustration">
            <div class="illustration-content">
                <div class="illustration-heading">Unified Infrastructure Control</div>
                <div class="illustration-subtitle">
                    Unified control for clusters, storage, and networking in one intuitive Silo HCI console.
                </div>
                <div class="illustration-graphic">
                    <div class="tower">
                        <span class="tower-step"></span>
                    </div>
                    <div class="tower">
                        <span class="tower-step"></span>
                    </div>
                    <div class="tower">
                        <span class="tower-step"></span>
                    </div>
                    <div class="connector"></div>
                </div>
            </div>
            <div class="illustration-footer">
                <div class="icon" style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.15);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span>High-availability monitoring with automated backups and optional 2FA.</span>
            </div>
        </div>
        <div class="login-panel">
            <div class="brand-badge">
                <img src="/assets/images/silo-logo.svg" alt="Silo Platform Logo">
            </div>
            <div class="login-header">
                <h1>Welcome to Silo HCI</h1>
                <p>Sign in to manage your infrastructure dashboard.</p>
            </div>

            <form class="form-login" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                </div>

                <div class="login-actions">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" onclick="handleForgotPassword(event)">Forgot password?</a>
                </div>

                <button type="submit" class="login-button" id="loginBtn">
                    <i class="fas fa-sign-in-alt loading" id="loadingIcon"></i>
                    <span id="btnText">Sign In</span>
                </button>
            </form>
            <div id="dynamicTwoFA"></div>

            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> Silo HCI Platform. All rights reserved.</p>
                <div class="vendor-credit">
                    <img src="/assets/images/iwm-logo.svg" alt="IWM Logo">
                    <div class="vendor-text">
                        <span class="vendor-name">Innovation Wisdom Mastery</span>
                        <span class="vendor-tagline">Innovation • Wisdom • Mastery</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
        const flashError = <?php echo json_encode($error); ?>;
        const flashMessage = <?php echo json_encode($message); ?>;

const API_PREFIX = '<?php echo $config['api']['prefix']; ?>';
const apiHost = '<?php echo $config['api']['host']; ?>';
const apiPort = '<?php echo $config['api']['port']; ?>';
let API_URL = `${window.location.origin.replace(/\/$/, '')}${API_PREFIX}`;

if (!window.location.origin.startsWith('http')) {
    API_URL = `http://${apiHost}:${apiPort}${API_PREFIX}`;
}

const FormalAlert = Swal.mixin({
    customClass: {
        popup: 'swal2-silo-popup',
        title: 'swal2-silo-title',
        htmlContainer: 'swal2-silo-text',
        confirmButton: 'swal2-silo-confirm',
        cancelButton: 'swal2-silo-cancel'
    },
    buttonsStyling: false,
    showClass: {
        popup: 'swal2-animate-silo-in'
    },
    hideClass: {
        popup: 'swal2-animate-silo-out'
    }
});

        document.addEventListener('DOMContentLoaded', () => {
            if (flashError) {
        FormalAlert.fire({
            icon: 'error',
            title: 'Authentication Required',
            text: flashError,
            confirmButtonText: 'Dismiss'
                });
            } else if (flashMessage) {
        FormalAlert.fire({
            icon: 'info',
            title: 'Information',
            text: flashMessage,
            confirmButtonText: 'Understood'
                });
            }
        });

async function establishSession(user) {
    if (!user || typeof user !== 'object') {
        throw new Error('Missing user information');
    }

    const response = await fetch('/session/sync', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ user })
    });

    if (!response.ok) {
        const details = await response.json().catch(() => ({}));
        throw new Error(details.error || 'Failed to establish session');
    }
}

const twoFAContainer = document.getElementById('dynamicTwoFA');
let otpInputs = [];
let otpHiddenInput = null;

function ensureTwoFAForm() {
    let form = document.getElementById('twoFAForm');
    if (form) {
        return form;
    }

    twoFAContainer.innerHTML = `
        <form class="form-2fa" id="twoFAForm">
            <p style="text-align: center; margin-bottom: 20px; color: #6b7280;">
                Enter the 6-digit code from your authenticator app
            </p>
            <div class="otp-input" id="otpInputs">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <input type="hidden" id="otpCode">
            </div>
            <button type="submit" class="login-button">
                <i class="fas fa-check loading" id="loadingIcon2"></i>
                <span id="btnText2">Verify Code</span>
            </button>
            <p style="text-align: center; margin-top: 15px;">
                <a href="#" id="backToLogin" style="color: #667eea; text-decoration: none; font-size: 14px;">Back to Login</a>
            </p>
        </form>
    `;

    form = document.getElementById('twoFAForm');
    otpInputs = Array.from(form.querySelectorAll('.otp-box'));
    otpHiddenInput = form.querySelector('#otpCode');

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            updateOTPCode();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    form.addEventListener('submit', handleTwoFASubmit);
    form.querySelector('#backToLogin').addEventListener('click', backToLogin);

    return form;
}

function updateOTPCode() {
    if (!otpHiddenInput) return;
    const code = otpInputs.map(input => input.value).join('');
    otpHiddenInput.value = code;
}

function showTwoFAForm() {
    const form = ensureTwoFAForm();
    document.querySelector('.form-login').classList.add('hidden');
    form.classList.add('active');
    if (otpInputs.length > 0) {
        otpInputs[0].focus();
    }
}

// Login Form Handler
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const btn = document.getElementById('loginBtn');
    const loadingIcon = document.getElementById('loadingIcon');
    const btnText = document.getElementById('btnText');
    
    btn.disabled = true;
    loadingIcon.classList.add('active');
    btnText.textContent = 'Signing in...';
    
    try {
        const response = await fetch(API_URL + '/auth/login', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            if (data.require_2fa) {
                showTwoFAForm();
            } else {
                try {
                    await establishSession(data.user);
                    let successText = data.message || 'You will be redirected to the dashboard momentarily.';
                    if (flashMessage) {
                        successText = `${flashMessage}. You will be redirected to the dashboard momentarily.`;
                    }
                    await FormalAlert.fire({
                        icon: 'success',
                        title: 'Authentication Successful',
                        text: successText,
                        timer: 1500,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });
                    window.location.href = '/dashboard';
                } catch (syncError) {
                    console.error('Session sync error:', syncError);
                    await FormalAlert.fire({
                        icon: 'error',
                        title: 'Session Initialisation Failed',
                        text: syncError.message || 'The platform could not establish a control panel session.',
                        confirmButtonText: 'Dismiss'
                    });
                }
            }
        } else {
            await FormalAlert.fire({
                icon: 'error',
                title: 'Authentication Failed',
                text: data.error || 'Please verify your credentials and try again.',
                confirmButtonText: 'Try Again'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        await FormalAlert.fire({
            icon: 'error',
            title: 'Communication Error',
            text: error?.message ? `The request could not be completed: ${error.message}` : 'An unexpected error occurred while contacting the platform.',
            confirmButtonText: 'Dismiss'
        });
    } finally {
        btn.disabled = false;
        loadingIcon.classList.remove('active');
        btnText.textContent = 'Sign In';
    }
});

async function handleTwoFASubmit(e) {
    e.preventDefault();

    const form = e.currentTarget;
    const code = otpHiddenInput ? otpHiddenInput.value : '';

    if (code.length !== 6) {
        await FormalAlert.fire({
            icon: 'warning',
            title: 'Verification Code Required',
            text: 'Please enter the six-digit code from your authenticator application to continue.',
            confirmButtonText: 'Understood'
        });
        return;
    }

    const btn = form.querySelector('button');
    const loadingIcon = form.querySelector('.loading');
    const btnText = form.querySelector('span');

    btn.disabled = true;
    loadingIcon.classList.add('active');
    btnText.textContent = 'Verifying...';

    try {
        const response = await fetch(API_URL + '/auth/verify-2fa', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ code })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            try {
                await establishSession(data.user);
                let successText = data.message || 'You will be redirected to the dashboard momentarily.';
                if (flashMessage) {
                    successText = `${flashMessage}. You will be redirected to the dashboard momentarily.`;
                }
                await FormalAlert.fire({
                    icon: 'success',
                    title: 'Verification Confirmed',
                    text: successText,
                    timer: 1500,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
                window.location.href = '/dashboard';
            } catch (syncError) {
                console.error('Session sync error:', syncError);
                await FormalAlert.fire({
                    icon: 'error',
                    title: 'Session Initialisation Failed',
                    text: syncError.message || 'The platform could not establish a control panel session.',
                    confirmButtonText: 'Dismiss'
                });
            }
        } else {
            await FormalAlert.fire({
                icon: 'error',
                title: 'Verification Failed',
                text: data.error || 'The provided authentication code could not be validated.',
                confirmButtonText: 'Try Again'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        await FormalAlert.fire({
            icon: 'error',
            title: 'Communication Error',
            text: error?.message ? `The request could not be completed: ${error.message}` : 'An unexpected error occurred while contacting the platform.',
            confirmButtonText: 'Dismiss'
        });
    } finally {
        btn.disabled = false;
        loadingIcon.classList.remove('active');
        btnText.textContent = 'Verify Code';
    }
}

function handleForgotPassword(event) {
    event.preventDefault();
    FormalAlert.fire({
        icon: 'info',
        title: 'Assistance Required',
        text: 'Please contact your platform administrator to request a credential reset.',
        confirmButtonText: 'Dismiss'
    });
}

function backToLogin(e) {
    e.preventDefault();
    const loginForm = document.querySelector('.form-login');
    const form = document.getElementById('twoFAForm');
    if (!form) return;

    loginForm.classList.remove('hidden');
    form.classList.remove('active');
    otpInputs.forEach(input => input.value = '');
    updateOTPCode();
}
</script>

</body>
</html>
