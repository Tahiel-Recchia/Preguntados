
  // === ELIMINAR ===
  function abrirModalEliminarCategoria(id) {
    document.getElementById("categoriaAEliminar").value = id;
    document.getElementById("modal-eliminar-categoria").classList.remove("hidden");
  }
  
  function cerrarModalEliminarCategoria() {
    document.getElementById("modal-eliminar-categoria").classList.add("hidden");
  }

  // === EDITAR ===
  function abrirModalEditarCategoria(id) {
    document.getElementById("linkEditarCategoria").href = `/paneleditor/editarcategoria/${id}`;
    document.getElementById("modal-editar-categoria").classList.remove("hidden");
  }

  function cerrarModalEditarCategoria() {
    document.getElementById("modal-editar-categoria").classList.add("hidden");
  }

