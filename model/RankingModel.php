<?php
class RankingModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getRankingLimitado($limite){
        // Incluir fotoDePerfil renombrada a fotoPerfil para que la vista la consuma fácilmente
        $sql = "SELECT id, nombreDeUsuario, puntajeAcumulado, fotoDePerfil AS fotoPerfil FROM usuario ORDER BY puntajeAcumulado DESC LIMIT ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($fila as $index => &$usuario) {
            $usuario['posicion'] = $index + 1;
        }
        unset($usuario);
        return $fila;
    }
}


