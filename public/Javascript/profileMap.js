let map;

document.addEventListener("DOMContentLoaded", async function () {
    const mapElement = document.getElementById("map");
    const direccion = mapElement.dataset.direccion;
    async function obtenerCoordenadas(direccion) {
        try{
            const res = await fetch(
                `https://nominatim.openstreetmap.org/search?q=${direccion}&format=json&limit=1`
            );

            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }

            const data = await res.json();
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
        map = L.map("map").setView([-34.6, -63.6], 4); //Poner una direccion predeterminada
    } else {
        map = L.map("map").setView([coordenadas.lat, coordenadas.lon], 13);
    }

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap",
    }).addTo(map);


});