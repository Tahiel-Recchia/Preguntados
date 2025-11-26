
function toggleFormularioCategoria() {
      const form = document.getElementById("formulario-categoria");
      form.classList.toggle("hidden");
  }
  // === ELIMINAR ===
  function abrirModalEliminarCategoria(id) {
    document.getElementById("categoriaAEliminar").value = id;
    document.getElementById("modal-eliminar-categoria").classList.remove("hidden");
  }
  
  function cerrarModalEliminarCategoria() {
    document.getElementById("modal-eliminar-categoria").classList.add("hidden");
  }

  // === EDITAR ===
  function abrirModalEditarCategoria(id, descripcion, color, imagen) {
      // Setear valores en inputs
      document.getElementById("edit_cat_id").value = id;
      document.getElementById("edit_cat_descripcion").value = descripcion;
      document.getElementById("edit_cat_color").value = color;

      // Previsualización de imagen actual
      document.getElementById("edit_cat_imagen_preview").src = imagen;

      // Mostrar modal
      document.getElementById("modal-editar-categoria").classList.remove("hidden");
  }

  function cerrarModalEditarCategoria() {
      document.getElementById("modal-editar-categoria").classList.add("hidden");
  }
