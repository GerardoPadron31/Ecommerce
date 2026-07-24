<?php
require __DIR__ . '/config.php';

if (!usuario_autenticado()) {
    header('Location: main.php');
    exit;
}

// ---------------------------------------------------------
// Acciones (eliminar / actualizar) llegan siempre por POST
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['action'] ?? '';

    if ($accion === 'delete') {
        $emailBorrar = $_POST['email'] ?? '';
        eliminar_usuario($emailBorrar);

        // Si te borras a ti mismo, cerramos tu sesión de una vez
        if ($emailBorrar === $_SESSION['user_email']) {
            header('Location: logout.php');
            exit;
        }

        header('Location: users.php?deleted=1');
        exit;
    }

    if ($accion === 'update') {
        $emailActual = $_POST['email_actual'] ?? '';
        $nombre = trim($_POST['name'] ?? '');
        $nuevoEmail = trim($_POST['email'] ?? '');
        $nuevaPassword = trim($_POST['password'] ?? '');

        if ($nombre === '' || !filter_var($nuevoEmail, FILTER_VALIDATE_EMAIL)) {
            header('Location: users.php?edit=' . urlencode($emailActual) . '&error=invalid');
            exit;
        }
        if ($nuevaPassword !== '' && strlen($nuevaPassword) < 8) {
            header('Location: users.php?edit=' . urlencode($emailActual) . '&error=weak');
            exit;
        }

        $ok = actualizar_usuario($emailActual, $nombre, $nuevoEmail, $nuevaPassword ?: null);

        if (!$ok) {
            header('Location: users.php?edit=' . urlencode($emailActual) . '&error=exists');
            exit;
        }

        // Si editaste tu propia cuenta, refrescamos los datos de la sesión
        if ($emailActual === $_SESSION['user_email']) {
            $_SESSION['user_email'] = $nuevoEmail;
            $_SESSION['user_name'] = $nombre;
        }

        header('Location: users.php?updated=1');
        exit;
    }
}

$usuarios = cargar_usuarios();

$editando = $_GET['edit'] ?? null;
if ($editando !== null && !isset($usuarios[$editando])) {
    $editando = null;
}

$mensajesError = [
    'invalid' => 'Escribe un nombre y un correo válido.',
    'weak'    => 'La nueva contraseña debe tener al menos 8 caracteres.',
    'exists'  => 'Ese correo ya lo usa otra cuenta.',
];
$codigoError = $_GET['error'] ?? null;
$hayError = isset($mensajesError[$codigoError]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuarios · Estudio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="screen" style="grid-template-columns: 1fr;">
    <section class="panel-form">
      <div class="card users-card">

        <?php if ($editando !== null): ?>

          <!-- ---------------- Formulario de edición ---------------- -->
          <p class="card-eyebrow">Panel de usuarios</p>
          <h2>Editar usuario</h2>

          <?php if ($hayError): ?>
            <div class="alert" role="alert">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <?= htmlspecialchars($mensajesError[$codigoError]) ?>
            </div>
          <?php endif; ?>

          <form action="users.php" method="POST" novalidate>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="email_actual" value="<?= htmlspecialchars($editando, ENT_QUOTES) ?>">

            <div class="field">
              <input type="text" id="edit-name" name="name" placeholder=" " value="<?= htmlspecialchars($usuarios[$editando]['name'], ENT_QUOTES) ?>" required>
              <label for="edit-name">Nombre completo</label>
              <span class="underline"></span>
            </div>

            <div class="field">
              <input type="email" id="edit-email" name="email" placeholder=" " value="<?= htmlspecialchars($editando, ENT_QUOTES) ?>" required>
              <label for="edit-email">Correo electrónico</label>
              <span class="underline"></span>
            </div>

            <div class="field password">
              <input type="password" id="edit-password" name="password" placeholder=" " autocomplete="new-password" minlength="8">
              <label for="edit-password">Nueva contraseña (opcional)</label>
              <span class="underline"></span>
              <button type="button" class="toggle-pass" aria-label="Mostrar contraseña">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <p style="margin: -14px 0 22px; font-size: 12.5px; color: var(--text-muted);">
              Déjala en blanco para conservar la contraseña actual.
            </p>

            <div style="display:flex; gap:10px;">
              <button type="submit" class="btn">
                <span class="btn-label">Guardar cambios</span>
                <span class="spinner">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="9" stroke-opacity="0.25"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
                </span>
              </button>
              <a href="users.php" class="btn" style="background: var(--paper); color: var(--text-dark); border: 1.5px solid #ded6c4; display:flex; align-items:center; justify-content:center; text-decoration:none;">
                Cancelar
              </a>
            </div>
          </form>

        <?php else: ?>

          <!-- ---------------- Lista de usuarios ---------------- -->
          <div class="table-toolbar">
            <div>
              <p class="card-eyebrow" style="margin:0;">Panel de usuarios</p>
              <h2 style="margin: 6px 0 0;">Cuentas registradas</h2>
            </div>
            <a href="register.php" class="btn" style="width:auto; display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding: 12px 18px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Agregar usuario
            </a>
          </div>

          <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success" role="status">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              Cuenta eliminada. Ya puedes usar ese correo para registrar a alguien más.
            </div>
          <?php elseif (isset($_GET['updated'])): ?>
            <div class="alert alert-success" role="status">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              Los datos del usuario se actualizaron.
            </div>
          <?php elseif (isset($_GET['created'])): ?>
            <div class="alert alert-success" role="status">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              Cuenta creada correctamente.
            </div>
          <?php endif; ?>

          <?php if (empty($usuarios)): ?>
            <p class="empty-note">Todavía no hay usuarios registrados.</p>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Alta</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($usuarios as $email => $datos): ?>
                    <tr>
                      <td>
                        <?= htmlspecialchars($datos['name']) ?>
                        <?php if ($email === $_SESSION['user_email']): ?>
                          <span class="badge-you">Tú</span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($email) ?></td>
                      <td><?= htmlspecialchars($datos['created_at'] ?? '—') ?></td>
                      <td>
                        <div class="row-actions">
                          <a href="users.php?edit=<?= urlencode($email) ?>" class="icon-btn" aria-label="Editar">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                          </a>
                          <form action="users.php" method="POST" class="confirm-delete" data-confirm="¿Eliminar a <?= htmlspecialchars(addslashes($datos['name'])) ?>? Esta acción no se puede deshacer.">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>">
                            <button type="submit" class="icon-btn danger" aria-label="Eliminar">
                              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
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

          <p class="foot-note"><a href="dashboard.php" class="link">Volver al panel</a> · <a href="logout.php" class="link">Cerrar sesión</a></p>

        <?php endif; ?>

      </div>
    </section>
  </div>

<script src="assets/script.js"></script>
</body>
</html>
