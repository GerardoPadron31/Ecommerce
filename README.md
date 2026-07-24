# Login "Tinta y Latón"

Sistema de acceso en PHP con diseño propio: panel oscuro con una constelación
animada en canvas y un candado que se dibuja al cargar, junto a una tarjeta
de acceso con etiquetas flotantes, transición del botón y aviso de error
con animación de sacudida.

## Archivos

```
login-php/
├── main.php              → formulario de acceso (vista principal)
├── register.php          → formulario para crear una cuenta nueva
├── forgot-password.php   → pide el correo y genera el enlace de recuperación
├── reset-password.php    → define la nueva contraseña usando el token
├── users.php              → panel para ver, editar y eliminar usuarios
├── login.php              → procesa el POST del login y valida credenciales
├── dashboard.php           → página protegida tras iniciar sesión
├── logout.php              → cierra la sesión
├── config.php              → sesión + carga/guardado de usuarios + tokens
├── data/
│   └── users.json           → se crea solo, aquí quedan usuarios y tokens
└── assets/
    ├── style.css             → sistema de diseño y animaciones
    └── script.js              → constelación, mostrar/ocultar contraseña, confirmaciones
```

## Panel de usuarios

Desde `dashboard.php` hay un botón "Ver usuarios" que lleva a `users.php`,
donde puedes:

- **Ver** el nombre, correo y fecha de alta de cada cuenta registrada.
- **Editar** cualquier cuenta (nombre, correo, y opcionalmente una
  contraseña nueva — si dejas ese campo vacío, se conserva la actual).
- **Eliminar** una cuenta (pide confirmación antes de borrar). En cuanto
  la eliminas, ese correo queda libre para volver a registrarlo desde
  "Agregar usuario" o desde `register.php`.

Importante: en esta demostración **cualquier persona que inicie sesión
puede administrar a todos los usuarios**, no hay roles ni permisos.
Para un proyecto real, agrega una columna `role` (por ejemplo `admin` o
`user`) a cada registro y valida en `users.php` que solo los
administradores puedan entrar ahí.

## Cómo probarlo

Necesitas PHP instalado (8.0 o superior recomendado). Desde la carpeta del
proyecto:

```bash
php -S localhost:8000
```

Abre `http://localhost:8000/main.php` en tu navegador.

**Credenciales de prueba:**
- Correo: `ana@estudio.com`
- Contraseña: `SeguraClave123!`

También puedes crear una cuenta nueva desde `register.php` (el enlace
"Crea una aquí" en la pantalla de acceso te lleva ahí). Los usuarios nuevos
se guardan en `data/users.json`, así que persisten mientras conserves ese
archivo — bórralo si quieres reiniciar a solo el usuario de prueba.

## Recuperar contraseña

El flujo es:

1. `forgot-password.php` — el usuario escribe su correo. Si existe, se
   genera un token aleatorio (`bin2hex(random_bytes(32))`) válido por
   1 hora, guardado junto al usuario en `data/users.json`.
2. Como este proyecto no tiene servidor de correo configurado, el enlace
   de recuperación se muestra directamente en pantalla (bloque "Modo
   demostración"), en lugar de enviarse por email.
3. `reset-password.php?token=...` — valida que el token exista y no haya
   vencido, y deja elegir una contraseña nueva.

Para producción, reemplaza el bloque comentado en `forgot-password.php`
por un envío real, por ejemplo con `mail()` o con una librería como
PHPMailer:

```php
$enlace = 'https://tu-dominio.com/reset-password.php?token=' . $token;
mail(
    $email,
    'Recupera tu contraseña',
    "Entra a este enlace para elegir una nueva contraseña:\n$enlace\n\nCaduca en 1 hora.",
    'From: no-responder@tu-dominio.com'
);
```

Y elimina el bloque que imprime `$enlaceDemo` en pantalla, ya que en
producción el enlace solo debe llegar por correo.

## Conectar una base de datos real

Todo lo que necesitas cambiar está en `config.php`. Ahora mismo
`cargar_usuarios()` y `guardar_usuarios()` leen y escriben `data/users.json`;
en producción, reemplázalas por consultas con PDO, por ejemplo:

```php
$pdo = new PDO('mysql:host=localhost;dbname=mi_app;charset=utf8mb4', 'usuario', 'contraseña');
$stmt = $pdo->prepare('SELECT name, password FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    // credenciales correctas
}
```

Las contraseñas en la base de datos deben guardarse siempre con
`password_hash()`, nunca en texto plano.

## Detalles de seguridad ya incluidos

- Contraseñas verificadas con `password_verify()` (nunca comparación directa).
- `session_regenerate_id(true)` al iniciar sesión, para evitar fijación de sesión.
- Cookies de sesión con `httponly`.
- Escape de salida con `htmlspecialchars()` en todo lo que vino del usuario.
- La cookie "Recordarme" solo guarda el correo, nunca la contraseña.

## Personalizar el diseño

Los colores, tipografías y tiempos de animación están centralizados como
variables CSS al inicio de `assets/style.css` (`:root`), así que puedes
ajustar la paleta o el ritmo de las animaciones desde un solo lugar.
