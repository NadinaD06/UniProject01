<?php
/**
 * Admin dashboard view
 * Displays statistics and user management options
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold">Admin Dashboard</h1>
            <p class="text-gray-600 mt-2">Manage users, view statistics, and handle reports</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Total Users</h2>
                        <p class="text-2xl font-semibold"><?= $stats['total_users'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Posts -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Total Posts</h2>
                        <p class="text-2xl font-semibold"><?= $stats['total_posts'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Reports -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Pending Reports</h2>
                        <p class="text-2xl font-semibold"><?= $stats['pending_reports'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Post Statistics -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="border-b px-6 py-4">
                <h2 class="text-xl font-semibold">Post Statistics</h2>
            </div>
            <div class="p-6">
                <!-- Period Selector -->
                <div class="mb-6">
                    <select
                        id="periodSelect"
                        class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="week">Last Week</option>
                        <option value="month">Last Month</option>
                        <option value="year">Last Year</option>
                    </select>
                </div>

                <!-- Statistics Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Posts
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Likes Received
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Comments Received
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="postStatsBody">
                            <!-- Stats will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Reports -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="border-b px-6 py-4">
                <h2 class="text-xl font-semibold">User Reports</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reported User
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reported By
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reason
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img
                                                src="<?= $report['reported_user_image'] ?: '/assets/images/default-avatar.png' ?>"
                                                alt="<?= htmlspecialchars($report['reported_username']) ?>"
                                                class="w-8 h-8 rounded-full mr-3"
                                            >
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($report['reported_username']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: <?= $report['reported_user_id'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img
                                                src="<?= $report['reporter_image'] ?: '/assets/images/default-avatar.png' ?>"
                                                alt="<?= htmlspecialchars($report['reporter_username']) ?>"
                                                class="w-8 h-8 rounded-full mr-3"
                                            >
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($report['reporter_username']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?= htmlspecialchars($report['reason']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= date('M j, Y g:i a', strtotime($report['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $report['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= ucfirst($report['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($report['status'] === 'pending'): ?>
                                            <button
                                                onclick="handleReport(<?= $report['id'] ?>, 'block')"
                                                class="text-red-600 hover:text-red-900 mr-3"
                                            >
                                                Block User
                                            </button>
                                            <button
                                                onclick="handleReport(<?= $report['id'] ?>, 'dismiss')"
                                                class="text-gray-600 hover:text-gray-900"
                                            >
                                                Dismiss
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load post statistics when period changes
    const periodSelect = document.getElementById('periodSelect');
    if (periodSelect) {
        periodSelect.addEventListener('change', loadPostStats);
        loadPostStats(); // Load initial stats
    }
});

// Load post statistics
async function loadPostStats() {
    const period = document.getElementById('periodSelect').value;
    
    try {
        const response = await fetch(`/api/admin/stats/posts?period=${period}`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('postStatsBody');
            tbody.innerHTML = '';
            
            data.stats.forEach(stat => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <img
                                src="${stat.profile_image || '/assets/images/default-avatar.png'}"
                                alt="${stat.username}"
                                class="w-8 h-8 rounded-full mr-3"
                            >
                            <div class="text-sm font-medium text-gray-900">
                                ${stat.username}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${stat.post_count}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${stat.likes_received}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${stat.comments_received}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Handle report
async function handleReport(reportId, action) {
    if (!confirm(`Are you sure you want to ${action} this report?`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/admin/reports/handle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                report_id: reportId,
                action: action
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to handle report');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while handling report');
    }
}
</script> 