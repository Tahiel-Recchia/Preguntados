let tiempo = 10; // segundos
const timer = document.getElementById('timer');
const form = document.getElementById('respuestaForm');

const cuentaRegresiva = setInterval(() => {
    tiempo--;
    timer.textContent = tiempo;
    if (tiempo <= 0) {
    clearInterval(cuentaRegresiva);
    // Tiempo agotado → redirigir al controlador de timeout
    window.location.href = "/preguntas/tiempoAgotado";
    }
}, 1000);