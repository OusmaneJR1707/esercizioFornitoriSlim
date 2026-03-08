<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SupplySystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; }
        .login-card { border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .brand-icon { font-size: 3rem; color: #0d6efd; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <main class="w-100" style="max-width: 600px; padding: 15px;">
        <div class="card login-card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="brand-icon mb-2">📦</div>
                    <h1 class="h4 fw-bold text-dark">SupplySystem</h1>
                    <p class="text-muted small">Accedi al pannello di gestione</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center small py-2" role="alert">
                        Credenziali non valide. Riprova.
                    </div>
                <?php endif; ?>

                <form action="/esercizioFornitoriSlim/frontend/login" method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="mario.rossi" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <button class="btn btn-primary w-100 py-2 fw-bold" type="submit">Accedi al Sistema</button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>