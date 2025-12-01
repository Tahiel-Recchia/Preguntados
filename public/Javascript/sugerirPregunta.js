document.addEventListener('DOMContentLoaded', function() {
    const cerrarSugerir = document.getElementById('cerrarSugerir');
    const modalSugerir = document.getElementById('modalSugerir');
    const formSugerir = document.getElementById('formSugerir');
    const btnAbrirModal = document.getElementById('btnSugerirPregunta'); // El botón que abre el modal

    cerrarSugerir.addEventListener('click', function(){
        modalSugerir.classList.remove('flex');
        modalSugerir.classList.add('hidden');
    });
    if (btnAbrirModal && modalSugerir) {
        btnAbrirModal.addEventListener('click', function() {
            modalSugerir.classList.remove('hidden');
            modalSugerir.classList.add('flex');
        });
    }

    // 3. Lógica para ENVIAR el formulario (AJAX)
    if (formSugerir) {
        formSugerir.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(formSugerir);
            const btnEnviar = formSugerir.querySelector('button[type="submit"]');

            fetch('/usuario/guardarSugerencia', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'fetch',
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error de red o servidor: ' + response.statusText);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (err) {
                            console.error("El servidor devolvió algo que no es JSON:", text);
                            throw new Error("El servidor devolvió un error (ver consola)");
                        }
                    });
                })
                .then(data => {
                    console.log('Respuesta del servidor:', data);

                    if (data.status === 'error') {
                        alert(data.message || 'Error al enviar la sugerencia');
                        return;
                    }

                    alert(`Sugerencia enviada correctamente. ${data.message || ''}`);

                    if (modalSugerir) {
                        modalSugerir.classList.remove('flex');
                        modalSugerir.classList.add('hidden');
                    }

                    formSugerir.reset();

                    if (btnEnviar) {
                        btnEnviar.textContent = 'Enviado';
                        btnEnviar.disabled = true;
                        btnEnviar.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                })
                .catch(err => {
                    console.error('Error capturado:', err);
                    alert('Hubo un error al procesar la solicitud. Revisa la consola.');
                });
        });
    }
});