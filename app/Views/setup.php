<div class="hero min-h-screen">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <div class="flex items-center justify-center mb-8">
                <img src="/favicon.png" alt="Backender" class="w-16 h-16 mr-3">
                <h1 class="text-5xl font-bold">Welcome to Backender</h1>
            </div>
            <p class="mb-6">Set up your admin account to get started</p>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                <a href="/login" class="btn btn-primary w-full">Go to Login</a>
            <?php else: ?>
                <form method="POST" action="/setup" class="card bg-base-100 shadow-xl p-6">
                    <?php if (isset($passwordRequirements)): ?>
                        <div class="alert alert-info mb-4 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Password: <?= htmlspecialchars($passwordRequirements) ?></span>
                        </div>
                    <?php endif; ?>
                    
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
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Confirm Password</span>
                        </label>
                        <input type="password" name="password_confirm" placeholder="••••••••" class="input input-bordered" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Create Account</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
