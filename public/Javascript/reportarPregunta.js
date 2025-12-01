const btnReportar = document.getElementById('btnReportarPregunta');
const modalReportar = document.getElementById('modalReportar');
const cerrarReportar = document.getElementById('cerrarReportar');
const reportTexto = document.getElementById('reportPreguntaTexto');
const reportIdField = document.getElementById('report_pregunta_id');

if (btnReportar && modalReportar) {
    btnReportar.addEventListener('click', function(){
        // Prefer hidden current-question element first (works after answer/result)
        const hidden = document.getElementById('__currentPreguntaText');
        if (hidden && hidden.textContent && hidden.textContent.trim().length) {
            if (reportTexto) reportTexto.textContent = 'Pregunta: ' + hidden.textContent.trim();
        } else {
            // Try to show the pregunta text if present in the page
            const preguntaEl = document.querySelector('.bg-[#343964] p') || document.querySelector('p');
            const texto = preguntaEl ? preguntaEl.textContent.trim() : '';
            if (reportTexto) reportTexto.textContent = texto ? ('Pregunta: ' + texto) : '';
        }
        // Obtener el ID de la pregunta desde el elemento oculto
        const hiddenId = document.getElementById('__currentPreguntaId');
        const preguntaId = hiddenId ? hiddenId.textContent.trim() : '';
        if (reportIdField) reportIdField.value = preguntaId;
        console.log('Abriendo modal reportar (error) - pregunta_id:', preguntaId);
        modalReportar.classList.remove('hidden'); modalReportar.classList.add('flex');
    });
}
if (cerrarReportar && modalReportar) {
    cerrarReportar.addEventListener('click', function(){ modalReportar.classList.remove('flex'); modalReportar.classList.add('hidden'); });
}

// Interceptar el envío del formulario de reportar para hacerlo via AJAX
const formReportar = document.getElementById('formReportar');
if (formReportar) {
    formReportar.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formReportar);
        const btnReportar = document.getElementById('btnReportarPregunta');

        fetch('/usuario/reportarPregunta', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
        })
            .then(response => response.json().catch(()=>({status:'ok'})))
            .then(data => {
                console.log('Respuesta reporte:', data);
                if (modalReportar) {
                    modalReportar.classList.remove('flex');
                    modalReportar.classList.add('hidden');
                }
                formReportar.reset();
                if (data.status === 'duplicate') {
                    alert('Ya reportaste esta pregunta.');
                } else if (data.status === 'error') {
                    alert('Error al enviar el reporte');
                    return; // no deshabilitar si error
                } else {
                    alert('Reporte enviado correctamente');
                }
                if (btnReportar) {
                    btnReportar.disabled = true;
                    btnReportar.classList.add('opacity-50','cursor-not-allowed');
                    btnReportar.textContent = 'Reporte enviado';
                }
            })
            .catch(error => {
                console.error('Error al enviar reporte:', error);
                alert('Error al enviar el reporte. Por favor intenta nuevamente.');
            });
    });
}