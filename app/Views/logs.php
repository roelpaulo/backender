<div x-data="{ filter: 'all' }">
    <h1 class="text-2xl sm:text-3xl font-bold mb-6">Logs</h1>
    
    <div class="tabs tabs-boxed mb-4 text-xs sm:text-sm">
        <a class="tab" :class="filter === 'all' && 'tab-active'" @click="filter = 'all'">All</a>
        <a class="tab" :class="filter === 'error' && 'tab-active'" @click="filter = 'error'">Errors</a>
        <a class="tab" :class="filter === 'request' && 'tab-active'" @click="filter = 'request'">Requests</a>
    </div>
    
    <?php if (empty($logs)): ?>
    <div class="alert">
        <span>No logs yet</span>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th class="min-w-[140px]">Time</th>
                    <th class="min-w-[80px]">Type</th>
                    <th class="min-w-[200px]">Message</th>
                    <th class="min-w-[80px]">Endpoint</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr x-show="filter === 'all' || filter === '<?= htmlspecialchars($log['type']) ?>'">
                    <td class="text-xs sm:text-sm opacity-70 whitespace-nowrap">
                        <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $log['type'] === 'error' ? 'error' : 'info' ?> badge-sm">
                            <?= htmlspecialchars($log['type']) ?>
                        </span>
                    </td>
                    <td class="font-mono text-xs sm:text-sm break-all">
                        <?= htmlspecialchars($log['message']) ?>
                    </td>
                    <td>
                        <?php if ($log['endpoint_id']): ?>
                        <a href="/endpoint/edit/<?= $log['endpoint_id'] ?>" class="link text-xs sm:text-sm">
                            #<?= $log['endpoint_id'] ?>
                        </a>
                        <?php else: ?>
                        <span class="opacity-50">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
