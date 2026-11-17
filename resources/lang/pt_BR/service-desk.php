<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status dos Tickets
    |--------------------------------------------------------------------------
    */
    'status' => [
        'open' => 'Aberto',
        'pending' => 'Pendente',
        'in_progress' => 'Em Andamento',
        'on_hold' => 'Em Espera',
        'resolved' => 'Resolvido',
        'closed' => 'Fechado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prioridades dos Tickets
    |--------------------------------------------------------------------------
    */
    'priority' => [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Origens dos Tickets
    |--------------------------------------------------------------------------
    */
    'source' => [
        'web' => 'Web',
        'email' => 'E-mail',
        'api' => 'API',
        'service_request' => 'Requisição de Serviço',
        'phone' => 'Telefone',
        'chat' => 'Chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de Comentário
    |--------------------------------------------------------------------------
    */
    'comment_type' => [
        'reply' => 'Resposta',
        'note' => 'Nota Interna',
        'system' => 'Sistema',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ações do Histórico
    |--------------------------------------------------------------------------
    */
    'history_action' => [
        'created' => 'Ticket criado',
        'status_changed' => 'Status alterado para :status',
        'priority_changed' => 'Prioridade alterada para :priority',
        'assigned' => 'Atribuído a :agent',
        'unassigned' => 'Agente removido',
        'department_changed' => 'Departamento alterado para :department',
        'category_changed' => 'Categoria alterada para :category',
        'comment_added' => 'Comentário adicionado',
        'attachment_added' => 'Anexo adicionado',
        'attachment_removed' => 'Anexo removido',
        'closed' => 'Ticket fechado',
        'reopened' => 'Ticket reaberto',
        'title_changed' => 'Título alterado',
        'merged' => 'Ticket mesclado com :reference',
        'tags_changed' => 'Tags atualizadas',
        'sla_applied' => 'Política de SLA aplicada',
        'sla_breached' => 'SLA violado',
        'escalated' => 'Ticket escalonado',
        'service_request_linked' => 'Requisição de serviço vinculada',
        'article_linked' => 'Artigo da base de conhecimento vinculado',
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA
    |--------------------------------------------------------------------------
    */
    'sla' => [
        'breach_type' => [
            'first_response' => 'Primeira Resposta',
            'next_response' => 'Próxima Resposta',
            'resolution' => 'Resolução',
        ],
        'applied' => 'Política de SLA ":policy" aplicada ao ticket :reference',
        'breached' => 'SLA de :type violado no ticket :reference',
        'near_breach' => 'SLA de :type próximo de ser violado no ticket :reference',
        'metric_met' => 'SLA de :type cumprido no ticket :reference',
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalonamento
    |--------------------------------------------------------------------------
    */
    'escalation' => [
        'action' => [
            'notify' => 'Notificar',
            'reassign' => 'Reatribuir',
            'change_priority' => 'Alterar Prioridade',
            'custom' => 'Personalizado',
        ],
        'triggered' => 'Regra de escalonamento ":rule" acionada no ticket :reference',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation
    |--------------------------------------------------------------------------
    */
    'automation' => [
        'trigger' => [
            'ticket_created' => 'Ticket Criado',
            'ticket_status_changed' => 'Status do Ticket Alterado',
            'comment_added' => 'Comentário Adicionado',
        ],
        'action' => [
            'reassign' => 'Reatribuir',
            'change_priority' => 'Alterar Prioridade',
            'change_status' => 'Alterar Status',
            'add_tag' => 'Adicionar Tag',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Base de Conhecimento
    |--------------------------------------------------------------------------
    */
    'knowledge_base' => [
        'article_status' => [
            'draft' => 'Rascunho',
            'published' => 'Publicado',
            'archived' => 'Arquivado',
        ],
        'visibility' => [
            'public' => 'Público',
            'internal' => 'Interno',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogo de Serviços
    |--------------------------------------------------------------------------
    */
    'service_catalog' => [
        'visibility' => [
            'public' => 'Público',
            'internal' => 'Interno',
            'draft' => 'Rascunho',
        ],
        'request_status' => [
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'in_progress' => 'Em Andamento',
            'fulfilled' => 'Concluído',
            'cancelled' => 'Cancelado',
        ],
        'approval_status' => [
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de Campo de Formulário
    |--------------------------------------------------------------------------
    */
    'form_field_type' => [
        'text' => 'Texto',
        'textarea' => 'Área de Texto',
        'select' => 'Seleção',
        'checkbox' => 'Caixa de Seleção',
        'radio' => 'Opção Única',
        'date' => 'Data',
        'datetime' => 'Data e Hora',
        'file' => 'Arquivo',
        'number' => 'Número',
        'email' => 'E-mail',
        'url' => 'URL',
        'tel' => 'Telefone',
        'toggle' => 'Alternador',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dias da Semana
    |--------------------------------------------------------------------------
    */
    'day_of_week' => [
        'sunday' => 'Domingo',
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terça-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sábado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificações
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'ticket_created' => [
            'subject' => 'Chamado aberto',
            'greeting' => 'Olá!',
            'body' => 'Seu chamado #:reference (:title) foi aberto. Nossa equipe vai analisá-lo e retornar em breve.',
            'department' => 'Departamento: :department',
            'priority' => 'Prioridade: :priority',
        ],
        'status_changed' => [
            'subject' => 'Status atualizado',
            'greeting' => 'Olá!',
            'body' => 'O status do chamado #:reference (:title) foi alterado.',
            'transition' => 'De :old para :new.',
        ],
        'ticket_assigned' => [
            'subject' => 'Chamado atribuído a você',
            'greeting' => 'Olá!',
            'body' => 'O chamado #:reference (:title) foi atribuído a você.',
            'priority' => 'Prioridade: :priority',
            'department' => 'Departamento: :department',
        ],
        'ticket_closed' => [
            'subject' => 'Chamado fechado',
            'greeting' => 'Olá!',
            'body' => 'O chamado #:reference (:title) foi fechado.',
            'info' => 'Se isso foi um engano ou você ainda precisa de ajuda, pode reabri-lo.',
        ],
        'new_comment' => [
            'subject' => 'Novo comentário',
            'greeting' => 'Olá!',
            'body' => ':author comentou no chamado #:reference (:title).',
            'comment_type' => 'Tipo: :type',
        ],
        'unknown_user' => 'Alguém',
        'sla_breached' => [
            'subject' => 'SLA violado',
            'greeting' => 'Olá!',
            'body' => 'O SLA de :breach_type foi violado no chamado #:reference (:title). É necessária atenção imediata.',
            'priority' => 'Prioridade: :priority',
        ],
        'sla_near_breach' => [
            'subject' => 'SLA prestes a vencer',
            'greeting' => 'Olá!',
            'body' => 'O SLA de :breach_type do chamado #:reference (:title) vence em :minutes minutos.',
            'priority' => 'Prioridade: :priority',
        ],
        'escalation' => [
            'subject' => 'Chamado escalonado',
            'greeting' => 'Olá!',
            'body' => 'O chamado #:reference (:title) foi escalonado (:breach_type, ação: :action).',
            'priority' => 'Prioridade: :priority',
        ],
        'approval_requested' => [
            'subject' => 'Aprovação solicitada: :service',
            'greeting' => 'Olá!',
            'body' => ':requester solicitou :service, que precisa da sua aprovação.',
            'action_required' => 'Analise a solicitação e registre sua decisão.',
        ],
        'approval_decision' => [
            'subject' => 'Decisão de aprovação: :service (:status)',
            'greeting' => 'Olá!',
            'body' => 'Sua solicitação de :service foi :status.',
            'comment' => 'Comentário: :comment',
        ],
        'operator_new_ticket' => [
            'subject' => 'Novo Chamado: :title',
            'greeting' => 'Olá!',
            'body' => ':requester abriu um novo chamado de suporte.',
            'title' => 'Título: :title',
            'priority' => 'Prioridade: :priority',
        ],
        'operator_requester_reply' => [
            'subject' => 'Nova Resposta: :title',
            'greeting' => 'Olá!',
            'body' => ':requester respondeu a um chamado de suporte.',
            'title' => 'Título: :title',
            'priority' => 'Prioridade: :priority',
        ],
        'view_ticket' => 'Ver Chamado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Geral
    |--------------------------------------------------------------------------
    */
    'ticket' => 'Ticket',
    'tickets' => 'Tickets',
    'department' => 'Departamento',
    'category' => 'Categoria',
    'attachment' => 'Anexo',
    'comment' => 'Comentário',
    'tag' => 'Tag',
    'article' => 'Artigo',
    'service' => 'Serviço',
];
