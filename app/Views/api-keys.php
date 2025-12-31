<div class="container mx-auto p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">API Keys</h1>
        <button class="btn btn-primary btn-sm sm:btn-md w-full sm:w-auto" onclick="showCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Generate API Key
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?= htmlspecialchars($_GET['success']) ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?= htmlspecialchars($_GET['error']) ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['key'])): ?>
        <div class="alert bg-gradient-to-r from-primary to-secondary text-primary-content mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <div class="flex-1">
                <h3 class="font-bold text-lg">🎉 API Key Generated Successfully!</h3>
                <p class="text-sm opacity-90 mb-3">Copy this key now - it won't be shown again for security reasons.</p>
                <div class="bg-base-100 text-base-content p-4 rounded-lg shadow-inner">
                    <code class="text-sm break-all select-all"><?= htmlspecialchars($_GET['key']) ?></code>
                </div>
                <button class="btn btn-sm btn-neutral mt-3 gap-2" onclick="copyKey('<?= htmlspecialchars($_GET['key']) ?>', this)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                    </svg>
                    Copy to Clipboard
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <p class="text-sm text-gray-400 mb-4">
                API keys authenticate requests to protected endpoints. Include the key in the <code class="badge">X-API-Key</code> header.
            </p>

            <?php if (empty($keys)): ?>
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <p class="text-gray-500">No API keys yet. Generate one to get started.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Key</th>
                                <th>Last Used</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keys as $key): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold"><?= htmlspecialchars($key['label']) ?></div>
                                    </td>
                                    <td>
                                        <code class="text-xs"><?= htmlspecialchars(\Backender\Services\ApiKeyService::maskKey($key['key'])) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($key['last_used']): ?>
                                            <span class="text-sm"><?= date('M j, Y g:i A', strtotime($key['last_used'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-sm"><?= date('M j, Y', strtotime($key['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" action="/api-key/delete" class="inline" onsubmit="return confirm('Delete this API key? This cannot be undone.')">
                                            <input type="hidden" name="id" value="<?= $key['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-error">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-6 card bg-base-200">
        <div class="card-body">
            <h3 class="card-title text-lg">Using API Keys</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold mb-2">cURL:</p>
                    <pre class="bg-base-300 p-3 rounded text-sm overflow-x-auto"><code>curl http://localhost:8080/api/your-endpoint \
  -H "X-API-Key: your_api_key_here"</code></pre>
                </div>
                <div>
                    <p class="text-sm font-semibold mb-2">JavaScript (fetch):</p>
                    <pre class="bg-base-300 p-3 rounded text-sm overflow-x-auto"><code>fetch('http://localhost:8080/api/your-endpoint', {
  headers: {
    'X-API-Key': 'your_api_key_here'
  }
})</code></pre>
                </div>
                <div>
                    <p class="text-sm font-semibold mb-2">Authorization Bearer (alternative):</p>
                    <pre class="bg-base-300 p-3 rounded text-sm overflow-x-auto"><code>curl http://localhost:8080/api/your-endpoint \
  -H "Authorization: Bearer your_api_key_here"</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<dialog id="createModal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Generate API Key</h3>
        <form method="POST" action="/api-key/create">
            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Label (e.g., "Mobile App", "Frontend")</span>
                </label>
                <input type="text" name="label" class="input input-bordered" placeholder="My Application" required autofocus>
            </div>
            <div class="modal-action">
                <button type="button" class="btn" onclick="document.getElementById('createModal').close()">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<script>
function showCreateModal() {
    document.getElementById('createModal').showModal();
}

function copyKey(key, button) {
    // Try clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(key).then(() => {
            showCopyFeedback(button, true);
        }).catch(() => {
            fallbackCopy(key, button);
        });
    } else {
        fallbackCopy(key, button);
    }
}

function fallbackCopy(key, button) {
    // Fallback for older browsers or HTTPS issues
    const textarea = document.createElement('textarea');
    textarea.value = key;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopyFeedback(button, true);
    } catch (err) {
        showCopyFeedback(button, false);
    }
    
    document.body.removeChild(textarea);
}

function showCopyFeedback(button, success) {
    const originalContent = button.innerHTML;
    
    if (success) {
        button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg> Copied!';
        button.classList.add('btn-success');
    } else {
        button.innerHTML = '❌ Failed - Copy manually';
    }
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.classList.remove('btn-success');
    }, 2000);
}
</script>
