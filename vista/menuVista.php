<?php
// Enlace al registro (puede ir arriba o eliminarse si ya no lo usás)
echo '<a href="/preguntados/login/register">Ir al registro</a>';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex flex-col items-center justify-center bg-[url('../imagenes/bg.jpg')] bg-cover bg-center min-h-screen text-white">

    <!-- Sección del perfil del usuario -->
    <?php if (isset($usuario)): ?>
        <div class="flex flex-col items-center mb-8">
            <img src="/preguntados/<?php echo htmlspecialchars($usuario['foto']); ?>"
                 alt="Foto de perfil"
                 class="w-32 h-32 rounded-full object-cover border-2 border-pink-500 mb-3">
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($usuario['name']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Enlace para cerrar sesión -->
    <?php if (isset($_SESSION["user"])): ?>
        <a href="/preguntados/login/logout"
           class="px-4 py-2 bg-gradient-to-r from-[#E65895] to-[#BC6BE8] rounded-full text-white font-semibold hover:opacity-90 transition">
            Cerrar sesión
        </a>
    <?php endif; ?>

</body>
</html>
