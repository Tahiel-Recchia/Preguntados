let map;

document.addEventListener("DOMContentLoaded", async function () {
    const container = L.DomUtil.get('map');
    if (container != null) {
        container._leaflet_id = null; // <--- ESTA LÍNEA ES MÁGICA
    }

    // Si la variable map todavía tiene algo, lo borramos también
    if (map) {
        map.remove();
        map = null;
    }
    const mapElement = document.getElementById("map");
    const direccion = mapElement.dataset.direccion;
    async function obtenerCoordenadas(direccion) {
        if (!direccion) {
            return null;
        }
        try{
            const direccionCodificada = encodeURIComponent(direccion);
            const res = await fetch(
                `/Api/buscarDireccion?direccion=${direccionCodificada}`
            );
            console.log(res);

            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }

            const data = await res.json();
            console.log(data);
            if (data && data.length > 0) {
                return {
                    lat: data[0].lat,
                    lon: data[0].lon
                };
            }else{ console.log("No se encontraron coordenadas para:", direccion);
                return null;
            }
        } catch (error) {
            console.log("No se pudo obtener la data", error);
            return null;
        }

    }

    let coordenadas = await obtenerCoordenadas(`${direccion}`);
    if (!coordenadas) {
        console.error("No se pudieron obtener las coordenadas. No se puede inicializar el mapa.");
        map = L.map("map").setView([-34.6, -63.6], 4); //Si falla poner una direccion predeterminada, en este caso Argentina
    } else {
        map = L.map("map").setView([coordenadas.lat, coordenadas.lon], 13);
    }

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap",
    }).addTo(map);


});