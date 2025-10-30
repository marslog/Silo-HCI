<?php
/**
 * Account Management Page
 */

$config = require dirname(__DIR__, 2) . '/../src/Config/config.php';

use Silo\Utils\Session;
use Silo\Services\ApiService;

Session::requireAdmin();

$active = 'system-account';
$api = new ApiService();

// Get all users with default empty array
$usersResponse = $api->get('/auth/users');
$users = [
    'data' => $usersResponse['data'] ?? [],
    'success' => $usersResponse['success'] ?? false
];
?>

<?php include dirname(__DIR__, 2) . '/components/header.php'; ?>

<div class="main-wrapper">
    <?php include dirname(__DIR__, 2) . '/components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Account Management</h1>
            <p class="page-subtitle">Manage user accounts and permissions</p>
        </div>

        <div class="accounts-container">
            <!-- User Accounts Table -->
            <div class="content-card accounts-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users"></i> User Accounts
                    </h2>
                    <button class="btn btn-primary" onclick="openNewUserModal()">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>

                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="data-table accounts-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>2FA</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users['data'])): ?>
                                    <?php foreach ($users['data'] as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="twofa-badge <?php echo $user['two_factor_enabled'] ? 'enabled' : 'disabled'; ?>">
                                                    <?php echo $user['two_factor_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon danger" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px;">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- New User Modal -->
        <div id="newUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New User</h2>
                    <button class="modal-close" onclick="closeNewUserModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="newUserForm">
                        <div class="form-group">
                            <label for="newUsername">Username</label>
                            <input type="text" id="newUsername" name="username" required>
                        </div>

                        <div class="form-group">
                            <label for="newEmail">Email</label>
                            <input type="email" id="newEmail" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="newPassword">Password</label>
                            <input type="password" id="newPassword" name="password" required>
                        </div>

                        <div class="form-group">
                            <label for="newFullName">Full Name</label>
                            <input type="text" id="newFullName" name="full_name">
                        </div>

                        <div class="form-group">
                            <label for="newRole">Role</label>
                            <select id="newRole" name="role" required>
                                <option value="user">User</option>
                                <option value="operator">Operator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeNewUserModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit User</h2>
                    <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId">

                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" id="editEmail" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="editFullName">Full Name</label>
                            <input type="text" id="editFullName" name="full_name">
                        </div>

                        <div class="form-group">
                            <label for="editRole">Role</label>
                            <select id="editRole" name="role" required>
                                <option value="user">User</option>
                                <option value="operator">Operator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="editActive" name="is_active">
                                <span>Active</span>
                            </label>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .accounts-container {
        display: grid;
        gap: 20px;
        max-width: 1200px;
    }

    .accounts-card .card-body {
        padding: 0;
    }

    .accounts-card .table-wrapper {
        overflow-x: auto;
    }

    .accounts-table tbody tr:hover {
        background: rgba(148, 163, 184, 0.08);
        transition: background 0.2s ease;
    }

    .role-badge,
    .twofa-badge,
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: var(--border-radius-full);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .role-admin {
        background: rgba(248, 113, 113, 0.18);
        color: #b91c1c;
    }

    .role-operator {
        background: rgba(251, 191, 36, 0.2);
        color: #92400e;
    }

    .role-user {
        background: rgba(134, 239, 172, 0.2);
        color: #047857;
    }

    .twofa-badge.enabled {
        background: rgba(134, 239, 172, 0.2);
        color: #047857;
    }

    .twofa-badge.disabled {
        background: rgba(148, 163, 184, 0.18);
        color: var(--gray-600);
    }

    .status-badge.active {
        background: rgba(134, 239, 172, 0.2);
        color: #047857;
    }

    .status-badge.inactive {
        background: rgba(248, 113, 113, 0.18);
        color: #b91c1c;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
    }

    .btn-icon {
        background: none;
        border: none;
        color: var(--blue-500);
        cursor: pointer;
        padding: 6px;
        border-radius: var(--border-radius);
        transition: all 0.2s ease;
    }

    .btn-icon:hover {
        background: rgba(102, 126, 234, 0.12);
        color: var(--purple-500);
    }

    .btn-icon.danger {
        color: #ef4444;
    }

    .btn-icon.danger:hover {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }

    .checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-600);
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: var(--border-radius-xl);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
    }

    .modal-header {
        background: var(--gradient-primary);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: var(--border-radius-xl) var(--border-radius-xl) 0 0;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 24px;
        opacity: 0.9;
        transition: opacity 0.2s;
    }

    .modal-close:hover {
        opacity: 1;
    }

    .modal-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--gray-700);
        font-size: 0.9rem;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: var(--border-radius);
        font-size: 14px;
        background: rgba(255, 255, 255, 0.95);
        color: var(--gray-800);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--blue-500);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }

    .form-group .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        margin-bottom: 0;
    }

    .form-group .checkbox-label input {
        width: auto;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .modal-actions .btn {
        min-width: 130px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: var(--border-radius);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary {
        background: var(--gradient-primary);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    }

    .btn-secondary {
        background: rgba(148, 163, 184, 0.18);
        color: var(--gray-700);
    }

    .btn-secondary:hover {
        background: rgba(148, 163, 184, 0.28);
    }

    @media (max-width: 768px) {
        .accounts-card .card-body {
            padding-bottom: var(--spacing-md);
        }
    }
</style>

<script>
const API_URL = '<?php echo "http://{$config['api']['host']}:{$config['api']['port']}{$config['api']['prefix']}"; ?>';

// Open new user modal
function openNewUserModal() {
    document.getElementById('newUserModal').classList.add('active');
}

function closeNewUserModal() {
    document.getElementById('newUserModal').classList.remove('active');
    document.getElementById('newUserForm').reset();
}

// Open edit user modal
function openEditUserModal() {
    document.getElementById('editUserModal').classList.add('active');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('active');
}

// Create new user
document.getElementById('newUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        username: document.getElementById('newUsername').value,
        email: document.getElementById('newEmail').value,
        password: document.getElementById('newPassword').value,
        full_name: document.getElementById('newFullName').value,
        role: document.getElementById('newRole').value
    };
    
    try {
        const response = await fetch(API_URL + '/auth/users', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Account created successfully!', 'success');
            closeNewUserModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error || 'Failed to create account', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

// Edit user
async function editUser(userId) {
    try {
        const response = await fetch(API_URL + '/auth/users/' + userId, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const user = data.user;
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editFullName').value = user.full_name || '';
            document.getElementById('editRole').value = user.role;
            document.getElementById('editIsActive').checked = user.is_active;
            
            openEditUserModal();
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Failed to load user data', 'error');
    }
}

// Update user
document.getElementById('editUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const userId = document.getElementById('editUserId').value;
    const formData = {
        email: document.getElementById('editEmail').value,
        full_name: document.getElementById('editFullName').value,
        role: document.getElementById('editRole').value,
        is_active: document.getElementById('editIsActive').checked
    };
    
    try {
        const response = await fetch(API_URL + '/auth/users/' + userId, {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Account updated successfully!', 'success');
            closeEditUserModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error || 'Failed to update account', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
});

// Delete user
async function deleteUser(userId, username) {
    if (!confirm(`Are you sure you want to delete the account "${username}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(API_URL + '/auth/users/' + userId, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Account deleted successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error || 'Failed to delete account', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred: ' + error.message, 'error');
    }
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    const container = document.querySelector('.accounts-container');
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => alert.remove(), 5000);
}

// Close modal when clicking outside
document.getElementById('newUserModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('newUserModal')) {
        closeNewUserModal();
    }
});

document.getElementById('editUserModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('editUserModal')) {
        closeEditUserModal();
    }
});
</script>

<?php include dirname(__DIR__, 2) . '/components/footer.php'; ?>
