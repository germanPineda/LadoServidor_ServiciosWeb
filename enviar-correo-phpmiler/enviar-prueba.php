<?php

    # code...

$nombre = $_POST["nombre"];
$email = $_POST["email"];
$telefono = $_POST["telefono"];
$mensaje = $_POST["mensaje"];

$body = "Nombre: " .$nombre . "<br>Email :" .$email . "<br>Telefono: " .$telefono. 
        "<br>Mensaje: ".$mensaje;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';


$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = 2;                                       // Enable verbose debug output
    $mail->isSMTP();                                            // Set mailer to use SMTP
    $mail->Host       = 'smtp.gmail.com';                        // Specify main and backup SMTP servers
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'correospruebatec@gmail.com';                     // SMTP username
    $mail->Password   = 'correos@123';                               // SMTP password
    $mail->SMTPSecure = 'tls';   //ssl o tls                               // Enable TLS encryption, `ssl` also accepted
    $mail->Port       = 587;    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );                                // TCP port to connect to

    //Recipients
    $mail->setFrom('correospruebatec@gmail.com', $nombre );
    $mail->addAddress('correospruebatec@gmail.com');     // Add a recipient
   /* $mail->addAddress('ellen@example.com');               // Name is optional
    $mail->addReplyTo('info@example.com', 'Information');
    $mail->addCC('cc@example.com');
    $mail->addBCC('bcc@example.com');*/

    // Attachments
   // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Es un comentario de '. $nombre;
    $mail->Body    = $body;
   // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo '<script>
        alert("Mensaje Correctamente enviado")
        window.history.go(-1);
    </script>'; //1'Mensaje Correctamente enviado';
} catch (Exception $e) {
    echo "Error al enviar mensaje: {$mail->ErrorInfo}";
}
?>