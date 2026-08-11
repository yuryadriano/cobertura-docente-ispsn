<?php
/**
 * Helper de Notificações Automáticas e Alertas Institucionais
 * sftcoordenacao — ISPSN 2026/27
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Notification {
    
    public static function add(int $planoId, string $estado, ?string $mensagem = null): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['system_notifications'] = $_SESSION['system_notifications'] ?? [];
        array_unshift($_SESSION['system_notifications'], [
            'plano_id'    => $planoId,
            'novo_estado' => $estado,
            'observacoes' => $mensagem,
            'timestamp'   => date('Y-m-d H:i:s')
        ]);
        $_SESSION['system_notifications'] = array_slice($_SESSION['system_notifications'], 0, 10);
    }
    
    /**
     * Processa e regista uma notificação de mudança de estado do plano de cobertura
     */
    public static function notifyStateChange(array $plano, string $novoEstado, ?string $observacoes, ?array $actorUser): array {
        $cursoId = (int)($plano['curso_id'] ?? 1);
        $anoLectivo = $plano['ano_lectivo'] ?? '2026/27';
        $actorName = $actorUser['nome'] ?? 'Utilizador do Sistema';
        $actorRole = $actorUser['perfil'] ?? 'coordenador';

        $notifData = [
            'plano_id'    => $plano['id'],
            'curso_id'    => $cursoId,
            'ano_lectivo' => $anoLectivo,
            'novo_estado' => $novoEstado,
            'observacoes' => $observacoes,
            'actor_nome'  => $actorName,
            'actor_perfil'=> $actorRole,
            'timestamp'   => date('Y-m-d H:i:s')
        ];

        // Guardar na pilha de notificações em sessão para exibição no cabeçalho/painel
        $_SESSION['system_notifications'] = $_SESSION['system_notifications'] ?? [];
        array_unshift($_SESSION['system_notifications'], $notifData);
        // Manter apenas as últimas 10 notificações
        $_SESSION['system_notifications'] = array_slice($_SESSION['system_notifications'], 0, 10);

        // Definir mensagem flash apropriada para o perfil ativo
        if ($novoEstado === 'Submetido') {
            $_SESSION['flash_success'] = "Plano submetido com sucesso! A Presidência e Gestão Académica foram notificadas para homologação.";
        } elseif ($novoEstado === 'Devolvido') {
            $_SESSION['flash_error'] = "Plano devolvido para retificação! Motivo indicado: \"{$observacoes}\"";
        } elseif ($novoEstado === 'Aprovado') {
            $_SESSION['flash_success'] = "Plano Aprovado e Homologado pela Presidência! As atribuições foram sincronizadas com o Gestão Escolar.";
        }

        // Simulação de Disparo de Email Corporativo (Log de Envio)
        self::logEmailDispatch($notifData);

        return $notifData;
    }

    /**
     * Regista o log do e-mail corporativo enviado aos envolvidos (@ispsn.org)
     */
    private static function logEmailDispatch(array $data): void {
        $logFile = __DIR__ . '/../../public/uploads/email_notifications.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $entry = sprintf(
            "[%s] NOTIFICAÇÃO EMAIL | Estado: %s | Curso ID: %d | Ator: %s (%s) | Obs: %s\n",
            $data['timestamp'],
            $data['novo_estado'],
            $data['curso_id'],
            $data['actor_nome'],
            $data['actor_perfil'],
            $data['observacoes'] ?? 'Sem observações'
        );

        @file_put_contents($logFile, $entry, FILE_APPEND);
    }

    /**
     * Retorna a lista de notificações ativas da sessão
     */
    public static function getNotifications(): array {
        return $_SESSION['system_notifications'] ?? [];
    }
}
