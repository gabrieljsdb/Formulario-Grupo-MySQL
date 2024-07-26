<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluir o autoload do Composer para o PHPMailer
require 'vendor/autoload.php';

// Incluir o arquivo de conexão com o banco de dados
require 'db_connection.php';

// Obtendo informações do formulário
$nomeEnvio = $_POST['nomeEnvio'];
$emailEnvio = $_POST['emailEnvio'];
$cnpjEnvio = $_POST['cnpjEnvio'];
$empresaEnvio = $_POST['empresaEnvio'];
$pessoas = $_POST['pessoas'];

// Inserir informações na tabela envios_empenho
$sql = "INSERT INTO envios_empenho (nome, email, cnpj, empresa) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $nomeEnvio, $emailEnvio, $cnpjEnvio, $empresaEnvio);

if ($stmt->execute()) {
    $envio_id = $stmt->insert_id;

    // Inserir informações dos inscritos na tabela curso_inscritos
    $sql_inscrito = "INSERT INTO curso_inscritos (nome, cpf, email, curso_id, cnpj, empresa, forma_pagamento_id, pago, pago_valor, pago_data, envio_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_inscrito = $conn->prepare($sql_inscrito);

    foreach ($pessoas as $pessoa) {
        // Definir a data atual se pago_data for '0000-00-00'
        $pago_data = ($pessoa['pago_data'] === '0000-00-00') ? date('Y-m-d') : $pessoa['pago_data'];

        $stmt_inscrito->bind_param(
            "sssssssidsi",
            $pessoa['nome'],
            $pessoa['cpf'],
            $pessoa['email'],
            $pessoa['curso_id'],
            $pessoa['cnpj'],
            $pessoa['empresa'],
            $pessoa['forma_pagamento_id'],
            $pessoa['pago'],
            $pessoa['pago_valor'],
            $pago_data,
            $envio_id
        );
        $stmt_inscrito->execute();
    }

    // Mensagem de resultado a ser exibida
    $resultMessage = "Inscrição realizada com sucesso!<br><br>";

    // Envio de e-mail para o inscrito
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Servidor SMTP para inscritos
        $mail->SMTPAuth = true;
        $mail->Username = 'teste1234@gmail.com';  // Usuário SMTP
        $mail->Password = 'teste123';    // Senha SMTP
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Desativar o debug SMTP
        $mail->SMTPDebug = 0;

        // Defina o conjunto de caracteres para UTF-8
        $mail->CharSet = 'UTF-8';

        // Remetente e destinatário do inscrito
        $mail->setFrom('teste123@gmail.com', 'TESTE');
        $mail->addAddress($emailEnvio);  // E-mail do inscrito

        // Conteúdo do e-mail para o inscrito
        $mail->isHTML(true);
        $mail->Subject = 'Inscrição Realizada com Sucesso';
        $mail->Body    = "Olá $nomeEnvio,<br><br>Sua inscrição foi realizada com sucesso.<br><br>Atenciosamente,<br>Equipe GB";
        $mail->AltBody = "Olá $nomeEnvio,\n\nSua inscrição foi realizada com sucesso.\n\nAtenciosamente,\nEquipe GB";

        $mail->send();
        $resultMessage .= 'E-mail enviado para o inscrito com sucesso!<br><br>';
    } catch (Exception $e) {
        $resultMessage .= "Erro ao enviar e-mail para o inscrito: {$mail->ErrorInfo}<br><br>";
    }

    // Envio de e-mail para os administradores
    $mailAdmin = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP (Ajuste aqui para usar o mesmo servidor, se aplicável)
        $mailAdmin->isSMTP();
        $mailAdmin->Host = 'smtp.gmail.com';  // Usar o mesmo servidor SMTP
        $mailAdmin->SMTPAuth = true;
        $mailAdmin->Username = 'teste123@gmail.com';  // Mesmas credenciais
        $mailAdmin->Password = 'teste1234';    // Mesma senha
        $mailAdmin->SMTPSecure = 'tls';
        $mailAdmin->Port = 587;

        // Desativar o debug SMTP
        $mailAdmin->SMTPDebug = 0;

        // Defina o conjunto de caracteres para UTF-8
        $mailAdmin->CharSet = 'UTF-8';

        // Remetente e destinatários dos administradores
        $mailAdmin->setFrom('teste123@gmail.com', 'OAB/SC');
        $mailAdmin->addAddress('teste123@gmail.com'); // E-mail do administrador
        $mailAdmin->addAddress('teste123@gmail.com'); // Outro e-mail do administrador, se necessário

        // Conteúdo do e-mail para os administradores
        $mailAdmin->isHTML(true);
        $mailAdmin->Subject = 'Nova Inscrição Recebida';
        $mailAdmin->Body    = "Uma nova inscrição foi realizada.<br><br>Nome: $nomeEnvio<br>Email: $emailEnvio<br>CNPJ: $cnpjEnvio<br>Empresa: $empresaEnvio";
        $mailAdmin->AltBody = "Uma nova inscrição foi realizada.\n\nNome: $nomeEnvio\nEmail: $emailEnvio\nCNPJ: $cnpjEnvio\nEmpresa: $empresaEnvio";

        $mailAdmin->send();
        $resultMessage .= 'E-mail enviado para os organizadores do evento com sucesso!<br>';
    } catch (Exception $e) {
        $resultMessage .= "Erro ao enviar e-mail para os organizadores: {$mailAdmin->ErrorInfo}<br>";
    }

} else {
    $resultMessage = "Erro ao realizar inscrição: " . $conn->error . "<br>";
}

$stmt->close();
$conn->close();

// HTML para exibir a mensagem e redirecionar
echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='refresh' content='4;url=index.html'>
    <title>Redirecionamento</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .message {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class='message'>
        <p>$resultMessage</p>
        <p>Você será redirecionado para a página inicial em 4 segundos...</p>
    </div>
</body>
</html>";
?>
