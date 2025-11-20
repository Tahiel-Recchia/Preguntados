
const main = document.getElementById("main");
const categoriasJSON = main.dataset.categorias;
const categorias = JSON.parse(categoriasJSON);
const canvas = document.getElementById('ruletaCanvas');
const ctx = canvas.getContext('2d');
const botonGirar = document.getElementById('girar');
const pResultado = document.getElementById('resultado');

const numCategorias = categorias.length;
const anguloPorSegmento = (2 * Math.PI) / numCategorias;
const radio = canvas.width / 2;
const centroX = canvas.width / 2;
const centroY = canvas.height / 2;

const colores = categorias.map(categoria => categoria.color);

console.log(categorias);

let anguloActual = 0;
let estaGirando = false;

// --- NUEVO: PRECARGA DE IMÁGENES ---
const imagenesCargadas = {};
let totalImagenesACargar = numCategorias;
let imagenesCargadasCount = 0;

function cargarImagenes() {
    return new Promise((resolve) => {
        if (numCategorias === 0) {
            resolve();
            return;
        }

        categorias.forEach(categoria => {
            const img = new Image();
            img.src = categoria.imagen;
            img.onload = () => {
                imagenesCargadas[categoria.descripcion] = img;
                imagenesCargadasCount++;
                if (imagenesCargadasCount === totalImagenesACargar) {
                    resolve(); // Todas las imágenes se cargaron
                }
            };
            img.onerror = () => {
                console.error(`Error al cargar la imagen: ${categoria.imagen}`);
                // Aca podemos poner un placeholder cualquier cosa
                imagenesCargadas[categoria.descripcion] = null; // si falla la imagen se pone null por ahora
                imagenesCargadasCount++;
                if (imagenesCargadasCount === totalImagenesACargar) {
                    resolve();
                }
            };
        });
    });
}


function dibujarRuleta() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.save();
    ctx.translate(centroX, centroY);
    ctx.rotate(anguloActual);

    for (let i = 0; i < numCategorias; i++) {
        const anguloInicio = i * anguloPorSegmento;
        const anguloFin = (i + 1) * anguloPorSegmento;
        const categoria = categorias[i];
        const imagen = imagenesCargadas[categoria.descripcion];

        // --- Dibujar cada segmento ---
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.arc(0, 0, radio - 10, anguloInicio, anguloFin);
        ctx.closePath();
        ctx.fillStyle = colores[i % colores.length];
        ctx.fill();
        ctx.strokeStyle = "#E2E4F3";
        ctx.lineWidth = 2;
        ctx.stroke();

        // --- Dibujar la IMAGEN ---
        ctx.save();

        const anguloMedio = anguloInicio + (anguloPorSegmento / 2);
        ctx.rotate(anguloMedio);
        ctx.rotate(Math.PI / 2);

        if (imagen) {
            //Si hay imagen, cargarla con la base mirando al centro de la ruleta
            const tamanoImagen = radio * 0.4; //Valor de tamaño de la imagen
            const x = -(tamanoImagen / 2);
            const y = -(radio * 0.6) - (tamanoImagen / 2);

            ctx.drawImage(imagen, x, y, tamanoImagen, tamanoImagen);



        } else {
            //Mostrar texto si la imágen falla
            ctx.fillStyle = "#E2E4F3";
            ctx.font = "bold 14px Arial";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(categoria.descripcion, radio * 0.6, 0);
        }

        ctx.restore();
    }
    ctx.restore();
}

function iniciarGiro() {
    if (estaGirando) return;
    if (numCategorias === 0) {
        pResultado.innerHTML = "No hay categorías para girar.";
        return;
    }

    estaGirando = true;
    botonGirar.disabled = true;
    pResultado.textContent = "Girando...";

    const indiceGanador = Math.floor(Math.random() * numCategorias);
    const categoriaGanadora = categorias[indiceGanador];

    const anguloMedioGanador = (indiceGanador * anguloPorSegmento) + (anguloPorSegmento / 2);
    const puntoDeParada = -Math.PI / 2;
    let anguloFinal = puntoDeParada - anguloMedioGanador;

    const vueltasCompletas = (Math.floor(Math.random() * 5) + 5) * (2 * Math.PI);
    anguloFinal += vueltasCompletas;

    const duracionAnimacion = 5000;

    animarGiroConEasing(anguloFinal, duracionAnimacion, categoriaGanadora);
}

function animarGiroConEasing(anguloDestino, duracion, ganador) {
    const inicio = performance.now();
    const anguloInicio = anguloActual;

    function frame(tiempo) {
        const tiempoPasado = tiempo - inicio;
        let progreso = tiempoPasado / duracion;

        if (progreso > 1) progreso = 1;

        const easeOutQuad = t => t * (2 - t);
        const progresoSuave = easeOutQuad(progreso);

        anguloActual = anguloInicio + (anguloDestino - anguloInicio) * progresoSuave;

        dibujarRuleta();

        if (progreso < 1) {
            requestAnimationFrame(frame);
        } else {
            anguloActual = anguloDestino;
            estaGirando = false;
            botonGirar.disabled = true;
            pResultado.textContent = ganador.descripcion;
            anguloActual = anguloActual % (2 * Math.PI);

            const categoriaId = ganador.id;
            setTimeout(() => {
                window.location.href = `/preguntas/base?categoria=${categoriaId}`;
            }, 500);
        }
    }
    requestAnimationFrame(frame);
}

// No se dibuja la ruleta de una, primero esperamos que las imágenes carguen.
pResultado.textContent = "Cargando ruleta...";
cargarImagenes().then(() => {

    dibujarRuleta();
    botonGirar.disabled = false;
    pResultado.textContent = "¡Listo para girar!";
}).catch(error => {
    console.error("Hubo un error cargando las imágenes:", error);
    pResultado.textContent = "Error al cargar la ruleta.";
    dibujarRuleta();
    botonGirar.disabled = true;
});

botonGirar.addEventListener('click', iniciarGiro);
botonGirar.disabled = true;