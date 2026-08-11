<?php
/**
 * Helper de Envio de E-mail por SMTP Nativo (Socket RFC 5321)
 * sftcoordenacao — Módulo de Cobertura Docente ISPSN 2026/27
 */

class MailHelper {

    /**
     * Envia e-mail corporativo autenticado via servidor SMTP institucional
     */
    public static function send(string $toEmail, string $subject, string $bodyHtml, string $altText = ''): array {
        $host     = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $port     = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
        $username = defined('SMTP_USER') ? SMTP_USER : 'suporte.ti@ispsn.org';
        $password = defined('SMTP_PASS') ? SMTP_PASS : '';
        $secure   = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : 'tls';
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Direção de TI — ISPSN';

        // Se o servidor SMTP estiver sem senha configurada (modo padrão local / ambiente de desenvolvimento)
        if (empty($host) || $host === 'localhost' || empty($password)) {
            // Tentar transporte mail() nativo do sistema
            $headers  = "From: $fromName <$username>\r\n";
            $headers .= "Reply-To: $username\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $sent = @mail($toEmail, $subject, $bodyHtml, $headers);
            if ($sent) {
                return [
                    'success' => true,
                    'is_smtp' => false,
                    'message' => "E-mail processado via mail() nativo para $toEmail."
                ];
            } else {
                return [
                    'success' => false,
                    'is_dev_mode' => true,
                    'message' => "O servidor SMTP institucional ($host) necessita de palavra-passe configurada no ficheiro config/config.php (constante SMTP_PASS)."
                ];
            }
        }

        // Conexão por Socket SMTP (RFC 5321)
        $socketHost = ($secure === 'ssl') ? "ssl://$host" : $host;
        $timeout = 10;
        $errno = 0;
        $errstr = '';

        $socket = @fsockopen($socketHost, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            return [
                'success' => false,
                'message' => "Falha na ligação ao servidor SMTP institucional ($host:$port): $errstr ($errno)."
            ];
        }

        stream_set_timeout($socket, $timeout);

        $getResponse = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 512)) {
                $response .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $response;
        };

        $sendCommand = function(string $cmd) use ($socket, $getResponse) {
            fputs($socket, $cmd . "\r\n");
            return $getResponse();
        };

        // Handshake SMTP
        $resp = $getResponse();
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "Resposta inválida do servidor SMTP: $resp"];
        }

        $sendCommand("EHLO " . gethostname());

        // STARTTLS Negotiation
        if ($secure === 'tls') {
            $starttlsResp = $sendCommand("STARTTLS");
            if (substr($starttlsResp, 0, 3) === '220') {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    fclose($socket);
                    return ['success' => false, 'message' => 'Falha na negociação de encriptação TLS com o servidor SMTP.'];
                }
                $sendCommand("EHLO " . gethostname());
            }
        }

        // Autenticação AUTH LOGIN
        $authResp = $sendCommand("AUTH LOGIN");
        if (substr($authResp, 0, 3) === '334') {
            $sendCommand(base64_encode($username));
            $passResp = $sendCommand(base64_encode($password));
            if (substr($passResp, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'message' => "Credenciais de e-mail institucional recusadas ($username): $passResp"];
            }
        }

        // Envio da Mensagem
        $sendCommand("MAIL FROM: <$username>");
        $rcptResp = $sendCommand("RCPT TO: <$toEmail>");
        if (substr($rcptResp, 0, 3) !== '250' && substr($rcptResp, 0, 3) !== '251') {
            fclose($socket);
            return ['success' => false, 'message' => "Endereço de destino rejeitado ($toEmail): $rcptResp"];
        }

        $dataResp = $sendCommand("DATA");
        if (substr($dataResp, 0, 3) === '354') {
            $headers  = "From: $fromName <$username>\r\n";
            $headers .= "To: <$toEmail>\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            $messageData = $headers . "\r\n" . $bodyHtml . "\r\n.";
            $sendResp = $sendCommand($messageData);
            $sendCommand("QUIT");
            fclose($socket);

            if (substr($sendResp, 0, 3) === '250') {
                return ['success' => true, 'is_smtp' => true, 'message' => "E-mail entregue com sucesso via SMTP para $toEmail."];
            } else {
                return ['success' => false, 'message' => "Erro ao entregar dados da mensagem: $sendResp"];
            }
        }

        fclose($socket);
        return ['success' => false, 'message' => 'Erro na instrução DATA do SMTP.'];
    }
}
