<?php

require_once 'utilidades/ConexionBD.php';
require_once 'controladores/contactos.php';

class usuarios
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "usuario";
    const ID_USUARIO = "idUsuario";
    const NOMBRE = "nombre";
    const CONTRASENA = "contrasena";
    const CORREO = "correo";
    const CLAVE_API = "claveApi";

    const ESTADO_URL_INCORRECTA = "URL incorrecta";
    const ESTADO_CREACION_EXITOSA = "Eres un maquina, crack, fiera";
    const ESTADO_CREACION_FALLIDA = "Creacion fallida";
    const ESTADO_FALLA_DESCONOCIDA = "Te topaste con una falla desconocida";
    const ESTADO_ERROR_BD = "Error con la base de datos";
    const ESTADO_PARAMETROS_INCORRECTOS = "Los parametros introducidos son incorrectos";
    const ESTADO_CLAVE_NO_AUTORIZADA = "La clave introducida no esta autorizada";
    const ESTADO_AUSENCIA_CLAVE_API = "No se introdujo una clave API";
    const ESTADO_LOGIN_CORRECTO = "Has entrado al sistema... hackerman";

    public static function post($peticion) {
        // Procesar post       
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    private function crear($datosUsuario) {
        $nombre = $datosUsuario->nombre;
    
        $contrasena = $datosUsuario->contrasena;
        $contrasenaEncriptada = self::encriptarContrasena($contrasena);
    
        $correo = $datosUsuario->correo;
    
        $claveApi = self::generarClaveApi();
    
        try {
    
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
    
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::CONTRASENA . "," .
                self::CLAVE_API . "," .
                self::CORREO . ")" .
                " VALUES(?,?,?,?)";
    
            $sentencia = $pdo->prepare($comando);
    
            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $contrasenaEncriptada);
            $sentencia->bindParam(3, $claveApi);
            $sentencia->bindParam(4, $correo);
    
            $resultado = $sentencia->execute();
    
            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function encriptarContrasena($contrasenaPlana) {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private function generarClaveApi() {
        return md5(microtime().rand());
    }

    private function registrar() {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $resultado = self::crear($usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Registro con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private function loguear() {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $usuario = json_decode($body);

        $correo = $usuario->correo;
        $contrasena = $usuario->contrasena;

        if (self::autenticar($correo, $contrasena)) {
            $usuarioBD = self::obtenerUsuarioPorCorreo($correo);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["correo"] = $usuarioBD["correo"];
                $respuesta["claveApi"] = $usuarioBD["claveApi"];
                return ["estado" => self::ESTADO_LOGIN_CORRECTO, "usuario" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Correo o contraseña inválidos"));
        }
    }

    private function autenticar($correo, $contrasena)
    {
        $comando = "SELECT contrasena FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";
    
        try {
    
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
    
            $sentencia->bindParam(1, $correo);
    
            $sentencia->execute();
    
            if ($sentencia) {
                $resultado = $sentencia->fetch();
    
                if (self::validarContrasena($contrasena, $resultado['contrasena'])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
        return password_verify($contrasenaPlana, $contrasenaHash);
    }

    private function obtenerUsuarioPorCorreo($correo)
    {
        $comando = "SELECT " .
            self::NOMBRE . "," .
            self::CONTRASENA . "," .
            self::CORREO . "," .
            self::CLAVE_API .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";
    
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
    
        $sentencia->bindParam(1, $correo);
    
        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }

    

    private function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID_USUARIO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";
    
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
    
        $sentencia->bindParam(1, $claveApi);
    
        $sentencia->execute();
    
        return $sentencia->fetchColumn(0) > 0;
    }

    private function obtenerIdUsuario($claveApi)
    {
        $comando = "SELECT " . self::ID_USUARIO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";
    
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
    
        $sentencia->bindParam(1, $claveApi);
    
        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['idUsuario'];
        } else
            return null;
    }

    public static function autorizar()
    {
        $cabeceras = apache_request_headers();
    
        if (isset($cabeceras["authorization"])) {
    
            $claveApi = $cabeceras["authorization"];
    
            if (self::validarClaveApi($claveApi)) {
                return self::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }
    
        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticación"));
        }
    }
}
?>