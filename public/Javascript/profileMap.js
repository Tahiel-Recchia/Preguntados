let map;

document.addEventListener("DOMContentLoaded", async function () {
    // 1. Limpieza preventiva del contenedor
    const container = L.DomUtil.get('map');
    if (container != null) {
        container._leaflet_id = null;
    }

    // Si la variable map todavía tiene algo, lo borramos también
    if (map) {
        map.remove();
        map = null;
    }

    const mapElement = document.getElementById("map");
    // Referencia al input donde se escribirá la dirección (asegúrate que tu input tenga este ID)
    const addressInput = document.getElementById("addressInput");
    const direccion = mapElement.dataset.direccion;

    // Variable para controlar el pin en el mapa
    let marker;

    async function obtenerCoordenadas(direccion) {
        if (!direccion) return null;
        try {
            const direccionCodificada = encodeURIComponent(direccion);
            const res = await fetch(`/Api/buscarDireccion?direccion=${direccionCodificada}`);

            if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);

            const data = await res.json();

            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat), // ParseFloat por seguridad
                    lon: parseFloat(data[0].lon)
                };
            } else {
                console.log("No se encontraron coordenadas para:", direccion);
                return null;
            }
        } catch (error) {
            console.log("No se pudo obtener la data", error);
            return null;
        }
    }

    // --- LÓGICA DE INICIO ---
    let coordenadas = await obtenerCoordenadas(`${direccion}`);

    if (!coordenadas) {
        console.error("No se pudieron obtener las coordenadas. Inicializando por defecto.");
        map = L.map("map").setView([-34.6, -63.6], 4);
    } else {
        map = L.map("map").setView([coordenadas.lat, coordenadas.lon], 15);
        // Si arrancamos con coordenadas, ponemos el marcador inicial
        marker = L.marker([coordenadas.lat, coordenadas.lon]).addTo(map);
    }

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap",
    }).addTo(map);

    // --- NUEVA FUNCIONALIDAD: CLICK EN EL MAPA ---
    map.on("click", async (e) => {
        const { lat, lng } = e.latlng;


        if (marker) marker.remove();
        marker = L.marker([lat, lng]).addTo(map);

        try {

            const response = await fetch(`/Api/obtenerDireccionPorCoordenadas?lat=${lat}&lon=${lng}`);

            if (!response.ok) throw new Error("Error obteniendo dirección");

            const data = await response.json();
            const address = data.address || {};

            const provincia = address.state || address.region || '';
            const pais = address.country || '';

            let textoUbicacion = '';

            if (provincia && pais) {
                textoUbicacion = `${provincia}, ${pais}`;
            } else if (pais) {
                textoUbicacion = pais;
            } else {
                textoUbicacion = "Ubicación desconocida";
            }

            if (addressInput) {
                addressInput.value = textoUbicacion;
            }

        } catch (error) {
            console.error("Error al obtener ubicación:", error);
            if (addressInput) addressInput.value = "Error de red";
        }
    });
});