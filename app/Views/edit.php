<div class="max-w-4xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="/" class="btn btn-ghost btn-xs sm:btn-sm mr-2 sm:mr-4">← Back</a>
        <h1 class="text-xl sm:text-3xl font-bold">Edit Endpoint</h1>
    </div>
    
    <div class="grid gap-6">
        <!-- Endpoint Details -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Details</h2>
                <form method="POST" action="/endpoint/update/<?= $endpoint['id'] ?>">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Name</span>
                        </label>
                        <input type="text" name="name" value="<?= htmlspecialchars($endpoint['name']) ?>" class="input input-bordered" required>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Method</span>
                            </label>
                            <select name="method" class="select select-bordered" required>
                                <?php foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method): ?>
                                <option value="<?= $method ?>" <?= $endpoint['method'] === $method ? 'selected' : '' ?>>
                                    <?= $method ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Path</span>
                            </label>
                            <input type="text" name="path" value="<?= htmlspecialchars($endpoint['path']) ?>" class="input input-bordered" required>
                        </div>
                    </div>
                    
                    <div class="form-control mb-4">
                        <label class="cursor-pointer label justify-start gap-4">
                            <input type="checkbox" name="require_auth" value="1" class="checkbox checkbox-primary" <?= $endpoint['require_auth'] ? 'checked' : '' ?>>
                            <div>
                                <span class="label-text font-semibold">Require API Key Authentication</span>
                                <p class="text-xs text-gray-400">If enabled, requests must include a valid API key in X-API-Key header</p>
                            </div>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Details</button>
                </form>
            </div>
        </div>
        
        <!-- Endpoint Logic -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-2">PHP Logic</h2>
                <p class="text-sm opacity-70 mb-4">Return a function that accepts $request parameter</p>
                
                <form method="POST" action="/endpoint/update-logic/<?= $endpoint['id'] ?>" id="logicForm">
                    <div class="form-control mb-4">
                        <div id="editor" class="w-full" style="height: 300px; border: 1px solid #374151; border-radius: 0.5rem;"></div>
                        <textarea name="logic" id="logicInput" style="display:none;" required><?= htmlspecialchars($logic) ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Logic</button>
                </form>
            </div>
        </div>
        
        <!-- Example -->
        <div class="card bg-base-200 shadow-xl">
            <div class="card-body">
                <h3 class="font-bold mb-2">Example Endpoint</h3>
                <div id="exampleEditor" class="w-full" style="height: 250px; border: 1px solid #374151; border-radius: 0.5rem;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
<script>
require.config({ 
    paths: { 
        vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs' 
    } 
});

require(['vs/editor/editor.main'], function() {
    // Main editor
    const initialCode = <?= json_encode($logic, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    
    const editor = monaco.editor.create(document.getElementById('editor'), {
        value: initialCode,
        language: 'php',
        theme: 'vs-dark',
        automaticLayout: true,
        minimap: { enabled: true },
        lineNumbers: 'on',
        scrollBeyondLastLine: false,
        fontSize: 14,
        tabSize: 4,
        insertSpaces: true,
        wordWrap: 'on'
    });
    
    // Example editor (read-only)
    const exampleCode = '<?php\n' +
'return function ($request) {\n' +
'    // Get query parameter\n' +
'    $name = $request->query(\'name\', \'World\');\n' +
'    \n' +
'    // Get POST data\n' +
'    $email = $request->input(\'email\');\n' +
'    \n' +
'    // Return array (becomes JSON)\n' +
'    return [\n' +
'        \'message\' => "Hello, {$name}!",\n' +
'        \'timestamp\' => time(),\n' +
'        \'method\' => $request->method()\n' +
'    ];\n' +
'};';
    
    const exampleEditor = monaco.editor.create(document.getElementById('exampleEditor'), {
        value: exampleCode,
        language: 'php',
        theme: 'vs-dark',
        automaticLayout: true,
        readOnly: true,
        minimap: { enabled: false },
        lineNumbers: 'on',
        scrollBeyondLastLine: false,
        fontSize: 14,
        wordWrap: 'on'
    });
    
    // Sync editor content to hidden textarea on form submit
    document.getElementById('logicForm').addEventListener('submit', function(e) {
        document.getElementById('logicInput').value = editor.getValue();
    });
});
</script>
