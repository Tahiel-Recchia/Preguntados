const timer = document.getElementById('timer');
const form = document.getElementById('respuestaForm');
const TIEMPO_MAXIMO = 10;

if (timer) {
    fetch('/Preguntas/obtenerHoraDeInicio')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                const inicioServidor = data.timestamp_inicio;
                const ahoraCliente = Math.floor(Date.now() / 1000);
                const segundosTranscurridos = ahoraCliente - inicioServidor;

                let tiempoReal = TIEMPO_MAXIMO - segundosTranscurridos;
                if (tiempoReal <= 0) {
                    window.location.href = "/preguntas/tiempoAgotado";
                    return;
                }

                iniciarCuentaRegresiva(tiempoReal);
            }
        })
        .catch(error => {
            console.error("Error de sincronización:", error);
            iniciarCuentaRegresiva(TIEMPO_MAXIMO);
        });
}

function iniciarCuentaRegresiva(tiempo) {
    timer.textContent = tiempo;

    const cuentaRegresiva = setInterval(() => {
        tiempo--;
        timer.textContent = tiempo;

        if (tiempo <= 0) {
            clearInterval(cuentaRegresiva);
            window.location.href = "/preguntas/tiempoAgotado";
        }
    }, 1000);
}