<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        #map {
            height: 250px !important;
            border-radius: 0.5rem;
            border: 1px solid #4b5563;
            z-index: 0;
        }

        .leaflet-container {
            background: #2c2f56 !important;
        }
    </style>
</head>

<body
    class="bg-[#1e1f3b] bg-[url('/preguntados/imagenes/bg.jpg')] bg-cover bg-center flex justify-center items-center min-h-screen">

    <main class="bg-[#343964] rounded-2xl shadow-2xl w-[33%] p-8 flex flex-col gap-5 text-[#E2E4F3]">
        <h1 class="text-2xl font-bold text-center mb-4">Crear cuenta</h1>

        <form class="grid grid-cols-2 gap-4" method="POST" action="/preguntados/register/base"
            enctype="multipart/form-data">
            <!-- Foto de perfil -->
            <div class="col-span-2 flex flex-col items-center mb-6">
                <label for="profilePic" class="cursor-pointer">
                    <img id="previewImage" src="/preguntados/imagenes/placeholder.png" alt="Previsualización"
                        class="w-[100px] h-[100px] rounded-full object-cover border-2 border-[#E65895] mb-3 object-contain">
                </label>
                <input type="file" name="profilePic" id="profilePic" accept="image/*" class="hidden">
                <p class="text-sm opacity-70">Haz clic en la imagen para subir una foto</p>
            </div>
            <!-- Nombre completo -->
            <div>
                <label class="block text-sm font-semibold mb-1">Nombre completo</label>
                <input type="text" name="name"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none"
                    placeholder="Ej: Juan Pérez" required>
            </div>
            <!-- Nombre de usuario -->
            <div>
                <label class="block text-sm font-semibold mb-1">Nombre de usuario</label>
                <input name="username" type="text"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none"
                    placeholder="Tu usuario" required>
            </div>


            <!-- Sexo -->
            <div>
                <label class="block text-sm font-semibold mb-1">Sexo</label>
                <select name="gender"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none" required>
                    <option>Masculino</option>
                    <option>Femenino</option>
                    <option>Prefiero no cargarlo</option>
                </select>
            </div>


            <!-- Año de nacimiento -->
            <div>
                <label class="block text-sm font-semibold mb-1">Año de nacimiento</label>
                <input type="number" name="birthdate"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none"
                    placeholder="Ej: 1998" required>
            </div>

            <!-- País y ciudad -->
            <div class="col-span-2">
                <label class="block text-sm font-semibold mb-1">País y ciudad</label>
                <input id="addressInput" type="text" name="address"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none mb-2"
                    placeholder="Haz clic en el mapa o escribe aquí" required>
                <div id="map" class="w-full h-[250px] rounded-lg border border-gray-600"></div>
                <p class="text-xs opacity-70 mt-1">Haz clic en el mapa para seleccionar tu ubicación</p>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-semibold mb-1">Correo electrónico</label>
                <input name="email" type="email"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none"
                    placeholder="usuario@ejemplo.com" required>
            </div>

            <!-- Contraseña -->
            <div>
                <label class="block text-sm font-semibold mb-1">Contraseña</label>
                <input name="password" type="password"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none"
                    placeholder="********" required>
            </div>

            <!-- Repetir contraseña -->
            <div>
                <label class="block text-sm font-semibold mb-1">Repetir contraseña</label>
                <input name="passwordRepeated" type="password"
                    class="w-full p-2 rounded-md bg-[#2c2f56] border border-gray-500 focus:outline-none"
                    placeholder="********" required>
            </div>
            <!-- Botón de registro -->


            <button type="submit"
                class="col-span-2 justify-self-center w-2/3 mt-3 py-3 rounded-lg bg-gradient-to-r from-[#E65895] to-[#BC6BE8] text-white font-semibold hover:opacity-90 transition">Registrarse</button>


        </form>

        <p class="text-center text-sm mt-3">Al registrarte, recibirás un correo con un enlace para validar tu cuenta.
        </p>
    </main>

    <script>
        // Previsualización de imagen
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

        // Inicializar mapa
        const map = L.map('map').setView([-34.6037, -58.3816], 4); // Argentina por defecto
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker;

        map.on('click', async (e) => {
            const { lat, lng } = e.latlng;

            if (marker) marker.remove();
            marker = L.marker([lat, lng]).addTo(map);

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
                const data = await response.json();

                const city = data.address.city || data.address.town || data.address.village || '';
                const country = data.address.country || '';
                const fullAddress = country && city ? `${country}, ${city}` : (country || city);

                document.getElementById('addressInput').value = fullAddress;
            } catch (error) {
                console.error('Error al obtener ubicación:', error);
            }
        });
    </script>

</body>

</html>