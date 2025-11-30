<?php

class CategoriaModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getCategorias()
    {
        $sql = "SELECT * FROM categoria";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $filas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
        $stmt->close();
        return $filas;
    }

    public function guardarCategoria($descripcion, $color, $imagen)
    {
        $sql = "INSERT INTO categoria (descripcion, color, imagen) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sss", $descripcion, $color, $imagen);
        $stmt->execute();
        $stmt->close();
    }

     public function getCategoriaById($id)
    {
        $sql = "SELECT * FROM categoria WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function actualizarCategoria($id, $descripcion, $color, $imagen)
    {
        $sql = "UPDATE categoria SET descripcion = ?, color = ?, imagen = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssi", $descripcion, $color, $imagen, $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    public function getCategoriasConEstado($idUsuario){
        $categorias = $this->getCategorias();
        $categoriasProcesadas = [];
        $algunaDisponible = false;

        foreach ($categorias as $cat) {

            $sqlTotal = "SELECT COUNT(*) as total FROM pregunta WHERE id_categoria = ? and aprobada = 1";
            $stmt = $this->conexion->prepare($sqlTotal);
            $stmt->bind_param("i", $cat['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $total = $row["total"];
            $stmt->close();

            $sqlVistas = "SELECT COUNT(*) as vistas FROM usuario_pregunta up 
                      JOIN pregunta p ON up.pregunta_id = p.id 
                      WHERE p.id_categoria = ? AND up.usuario_id = ?";
            $stmt = $this->conexion->prepare($sqlVistas);
            $stmt->bind_param("ii", $cat['id'], $idUsuario);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $vistas = $row["vistas"];
            $stmt->close();


            if ($total > $vistas) {
                $cat['disponible'] = true;
                $algunaDisponible = true;
            } else {
                $cat['disponible'] = false;
            }

            $categoriasProcesadas[] = $cat;
        }

        if (!$algunaDisponible) {

            $sql = "DELETE FROM usuario_pregunta WHERE usuario_id = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();
            $stmt->close();

            foreach ($categoriasProcesadas as &$cat) {
                $cat['disponible'] = true;
            }
        }

        return $categoriasProcesadas;
    }


}