<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Seleccionar dirección</title>
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  />
  <style>
    #map {
      height: 400px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    input {
      width: 100%;
      margin-bottom: 10px;
      padding: 8px;
    }
  </style>
</head>
<body>

  <h2>Seleccioná una ubicación en el mapa 🗺️</h2>
  
  <div id="map"></div>

  <label>Localidad</label>
  <input type="text" id="localidad" placeholder="Localidad" />

  <label>Dirección (calle y altura)</label>
  <input type="text" id="direccion" placeholder="Dirección" />

  <label>Latitud</label>
  <input type="text" id="direccionLat" readonly />

  <label>Longitud</label>
  <input type="text" id="direccionLon" readonly />

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    let map, marcador;

    document.addEventListener("DOMContentLoaded", function () {
      // Inicializa el mapa centrado en Buenos Aires (puede ajustarse)
      map = L.map("map").setView([-34.670554, -58.562810], 13);

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap",
      }).addTo(map);

      // Elementos de los inputs
      const localidadInput = document.getElementById("localidad");
      const direccionInput = document.getElementById("direccion");
      const latInput = document.getElementById("direccionLat");
      const lonInput = document.getElementById("direccionLon");

      // Evento: clic en el mapa
      map.on("click", function (e) {
        const lat = e.latlng.lat;
        const lon = e.latlng.lng;

        // Mueve marcador
        if (marcador) map.removeLayer(marcador);
        marcador = L.marker([lat, lon]).addTo(map);

        // Llama a la API de Nominatim para obtener dirección
        fetch(
          `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`
        )
          .then((res) => res.json())
          .then((data) => {
            if (data.address) {
              // Localidad
              if (data.address.city)
                localidadInput.value = data.address.city;
              else if (data.address.town)
                localidadInput.value = data.address.town;
              else if (data.address.village)
                localidadInput.value = data.address.village;
              else localidadInput.value = "";

              // Dirección (calle + altura)
              const calle = data.address.road || "";
              const altura = data.address.house_number || "";
              direccionInput.value = `${calle} ${altura}`.trim();

              // Coordenadas
              latInput.value = lat.toFixed(6);
              lonInput.value = lon.toFixed(6);
            }
          })
          .catch((error) => {
            console.error("Error al obtener dirección:", error);
          });
      });
    });
  </script>
</body>
</html>
