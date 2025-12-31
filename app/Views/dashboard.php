<div x-data="dashboard()">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">API Endpoints</h1>
        <button @click="openCreateModal" class="btn btn-primary btn-sm sm:btn-md w-full sm:w-auto">+ New Endpoint</button>
    </div>
    
    <?php if (empty($endpoints)): ?>
    <div class="alert">
        <span>No endpoints created yet. Click "New Endpoint" to get started!</span>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th class="min-w-[120px]">Name</th>
                    <th class="min-w-[80px]">Method</th>
                    <th class="min-w-[100px]">Path</th>
                    <th class="min-w-[70px]">Status</th>
                    <th class="min-w-[140px]">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($endpoints as $endpoint): ?>
                <tr>
                    <td class="font-semibold"><?= htmlspecialchars($endpoint['name']) ?></td>
                    <td>
                        <span class="badge badge-<?= $endpoint['method'] === 'GET' ? 'success' : 'info' ?> badge-sm">
                            <?= htmlspecialchars($endpoint['method']) ?>
                        </span>
                    </td>
                    <td><code class="text-xs sm:text-sm break-all"><?= htmlspecialchars($endpoint['path']) ?></code></td>
                    <td>
                        <form method="POST" action="/endpoint/toggle/<?= $endpoint['id'] ?>" class="inline">
                            <input type="checkbox" 
                                   class="toggle toggle-success toggle-sm" 
                                   <?= $endpoint['enabled'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                        </form>
                    </td>
                    <td>
                        <div class="join">
                            <a href="/endpoint/edit/<?= $endpoint['id'] ?>" class="btn btn-xs sm:btn-sm join-item" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </a>
                            <form method="POST" action="/endpoint/delete/<?= $endpoint['id'] ?>" class="inline" 
                                  onsubmit="return confirm('Delete this endpoint?')">
                                <button type="submit" class="btn btn-xs sm:btn-sm btn-error join-item" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Create Modal -->
    <dialog id="createModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Create New Endpoint</h3>
            <form method="POST" action="/endpoint/create">
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Name</span>
                    </label>
                    <input type="text" name="name" placeholder="My Endpoint" class="input input-bordered" required>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Method</span>
                    </label>
                    <select name="method" class="select select-bordered" required>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                        <option value="PATCH">PATCH</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Path</span>
                    </label>
                    <input type="text" name="path" placeholder="/api/hello" class="input input-bordered" required>
                    <label class="label">
                        <span class="label-text-alt">Must start with /</span>
                    </label>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn" onclick="createModal.close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</div>

<script>
function dashboard() {
    return {
        openCreateModal() {
            createModal.showModal();
        }
    };
}
</script>
