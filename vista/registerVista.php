<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#1e1f3b] bg-[url('imagenes/bg.jpg')] bg-cover bg-center flex justify-center items-center min-h-screen">

<main class="bg-[#343964] rounded-2xl shadow-2xl w-[33%] p-8 flex flex-col gap-5 text-[#E2E4F3]">
    <h1 class="text-2xl font-bold text-center mb-4">Crear cuenta</h1>

    <form class="grid grid-cols-2 gap-4" method="POST" action="/preguntados/login/register" enctype="multipart/form-data">
        <!-- Foto de perfil -->
        <div class="col-span-2 flex flex-col items-center mb-6">
            <label for="profilePic" class="cursor-pointer">
                <img id="previewImage" src="imagenes/placeholder.png"
                     alt="Previsualización"
                     class="w-[100px] h-[100px] rounded-full object-cover border-2 border-[#E65895] mb-3 object-contain">
            </label>
            <input type="file" name="profilePic" id="profilePic" accept="image/*" class="hidden">
            <p class="text-sm opacity-70">Haz clic en la imagen para subir una foto</p>
        </div>
        <!-- Nombre completo -->
        <div>
            <label class="block text-sm font-semibold mb-1">Nombre completo</label>
            <input type="text" name="name" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="Ej: Juan Pérez">
        </div>

        <!-- Año de nacimiento -->
        <div>
            <label class="block text-sm font-semibold mb-1">Año de nacimiento</label>
            <input type="number" name="birth" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="Ej: 1998">
        </div>

        <!-- Sexo -->
        <div>
            <label class="block text-sm font-semibold mb-1">Sexo</label>
            <select name="gender" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none">
                <option>Masculino</option>
                <option>Femenino</option>
                <option>Prefiero no cargarlo</option>
            </select>
        </div>

        <!-- País y ciudad -->
        <div>
            <label  class="block text-sm font-semibold mb-1">País y ciudad</label>
            <input name="address" type="text" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="Seleccionar desde el mapa">
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-semibold mb-1">Correo electrónico</label>
            <input name="email" type="email" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="usuario@ejemplo.com">
        </div>

        <!-- Nombre de usuario -->
        <div>
            <label class="block text-sm font-semibold mb-1">Nombre de usuario</label>
            <input name="user" type="text" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="Tu usuario">
        </div>

        <!-- Contraseña -->
        <div>
            <label class="block text-sm font-semibold mb-1">Contraseña</label>
            <input name="password" type="password" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="********">
        </div>

        <!-- Repetir contraseña -->
        <div>
            <label class="block text-sm font-semibold mb-1">Repetir contraseña</label>
            <input name="passwordRepeated" type="password" class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" placeholder="********">
        </div>
            <!-- Botón de registro -->


        <button type="submit" class="col-span-2 justify-self-center w-2/3 mt-3 py-3 rounded-lg bg-gradient-to-r from-[#E65895] to-[#BC6BE8] text-white font-semibold hover:opacity-90 transition">Registrarse</button>


    </form>

    <p class="text-center text-sm mt-3">Al registrarte, recibirás un correo con un enlace para validar tu cuenta.</p>
</main>
<script>
    const inputFile = document.getElementById('profilePic');
    const previewImg = document.getElementById('previewImage');

    inputFile.addEventListener('change', () => {
        const file = inputFile.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => previewImg.src = e.target.result;
            reader.readAsDataURL(file);
        }
    });
</script>
</body>
</html>
