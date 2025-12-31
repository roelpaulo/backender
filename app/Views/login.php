<div class="hero min-h-screen">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <div class="flex items-center justify-center mb-8">
                <img src="/favicon.png" alt="Backender" class="w-12 h-12 mr-3">
                <h1 class="text-4xl font-bold">Backender</h1>
            </div>
            
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
