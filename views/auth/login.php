<?php
$message = $message ?? null;
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión — mi Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
    <main class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-2xl font-black">mi</div>
            <h1 class="text-2xl font-bold">mi Administrativo</h1>
            <p class="mt-2 text-sm text-slate-400">Ingrese a su espacio de trabajo</p>
        </div>
        <?php if ($message): ?>
            <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-300"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="/login" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <label class="block text-sm font-semibold">
                Usuario
                <input name="usuario" autocomplete="username" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-blue-500">
            </label>
            <label class="block text-sm font-semibold">
                Contraseña
                <input type="password" name="password" autocomplete="current-password" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-blue-500">
            </label>
            <button class="w-full rounded-xl bg-blue-600 px-4 py-3 font-bold transition hover:bg-blue-500">Iniciar sesión</button>
        </form>
    </main>
</body>
</html>
