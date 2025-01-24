<script>
    let inicioModulo = Date.now(); // Registrar el tiempo al entrar al módulo

    document.querySelector('form').addEventListener('submit', function () {
        let finInteraccion = Date.now(); // Registrar el tiempo al enviar el formulario
        let tiempoTotalInteraccion = (finInteraccion - inicioModulo) / 1000; // Tiempo en segundos
        alert("Tiempo total de interacción: " + tiempoTotalInteraccion.toFixed(2) + " segundos");
        // Puedes enviar este tiempo al backend como un campo oculto
        let tiempoInput = document.createElement('input');
        tiempoInput.type = 'hidden';
        tiempoInput.name = 'tiempo_interaccion';
        tiempoInput.value = tiempoTotalInteraccion;
        this.appendChild(tiempoInput);
    });
</script>
