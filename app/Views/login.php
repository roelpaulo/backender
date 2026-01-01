<div class="hero min-h-screen">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <div class="flex items-center justify-center mb-8">
                <img src="/favicon.png" alt="Backender" class="w-12 h-12 mr-3">
                <h1 class="text-4xl font-bold">Backender</h1>
            </div>
            
            <?php if (isset($isDemoMode) && $isDemoMode): ?>
            <div class="alert alert-info mb-4 text-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h3 class="font-bold">Demo Mode</h3>
                    <div class="text-xs">
                        <p><strong>Email:</strong> demo@backender.dev</p>
                        <p><strong>Password:</strong> DemoPass123!</p>
                        <p class="mt-2 text-warning">⚠️ All data will be reset when you logout</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/login" class="card bg-base-100 shadow-xl p-6">
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <input type="email" name="email" placeholder="admin@example.com" class="input input-bordered" required autofocus>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Password</span>
                    </label>
                    <input type="password" name="password" placeholder="••••••••" class="input input-bordered" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-full mb-2">Login</button>
                
                <div class="text-center">
                    <a href="/forgot-password" class="link link-hover text-sm">Forgot password?</a>
                </div>
            </form>
        </div>
    </div>
</div>
