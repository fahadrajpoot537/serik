<?php

return [
    'name' => 'Imobiliária',
    'settings' => 'Settings',
    'login_form' => 'Formulário de login',
    'register_form' => 'Formulário de registro',
    'forgot_password_form' => 'Esqueci o formulário de senha',
    'reset_password_form' => 'Formulário de redefinição de senha',
    'consult_form' => 'Formulário de Consulta',
    'review_form' => 'Formulário de revisão',
    'property_translations' => 'Traduções de propriedades',
    'project_translations' => 'Traduções de projetos',
    'theme_options' => [
        'slug_name' => 'URLs de imóveis',
        'slug_description' => 'Personalize os slugs usados ​​para páginas imobiliárias. Tenha cuidado ao modificar, pois isso pode afetar o SEO e a experiência do usuário. Se algo der errado, você pode redefinir o padrão digitando o valor padrão ou deixando em branco.',
        'page_slug_name' => ':page slug de página',
        'page_slug_description' => 'Será parecido com :slug quando você acessar a página. O valor padrão é :default.',
        'page_slug_already_exists' => 'O slug da página :slug já está em uso. Por favor escolha outro.',
        'page_slugs' => [
            'projects_city' => 'Projetos por Cidade',
            'projects_state' => 'Projetos por Estado',
            'properties_city' => 'Imóveis por Cidade',
            'properties_state' => 'Imóveis por Estado',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Redefinir senha',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nome:',
        'field_email' => 'E-mail:',
        'field_phone' => 'Telefone:',
        'field_subject' => 'Assunto:',
        'field_content' => 'Contente:',
        'field_address' => 'Endereço:',
        'field_package' => 'Pacote:',
        'field_price' => 'Preço:',
        'field_total' => 'Total:',
        'field_account' => 'Conta:',

        // Common greetings and closings
        'greeting_admin' => 'Olá administrador!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Prezado administrador,',
        'closing_regards' => 'Cumprimentos,',
        'closing_thank_you' => 'Obrigado',
        'closing_best_regards' => 'Atenciosamente,',

        // Common actions
        'action_view_property' => 'Ver propriedade',
        'action_verify_now' => 'Verifique agora',
        'action_reset_password' => 'Redefinir senha',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/crédito',
        'save_amount' => '(Salvar :amount)',
        'for_credits' => 'para :count créditos',

        // New pending property email
        'new_pending_property_message' => 'Uma nova propriedade criada por :author ":name" está aguardando aprovação.',

        // Property approved email
        'property_approved_greeting' => 'Olá :name,',
        'property_approved_message' => 'Sua propriedade ":property_name" foi aprovada com sucesso em :site_name.',
        'property_approved_access' => 'Agora você pode acessar o site e gerenciar seu imóvel.',
        'property_approved_view_edit' => 'Para visualizar ou editar sua propriedade, clique neste link:',

        // Property rejected email
        'property_rejected_greeting' => 'Olá :name,',
        'property_rejected_message' => 'Sua propriedade ":property_name" foi rejeitada com sucesso em :site_name.',
        'property_rejected_reason' => 'O motivo da rejeição é o seguinte:',
        'property_rejected_contact' => 'Se você acredita que esta decisão foi tomada por engano, entre em contato com nossa equipe de suporte em :email.',
        'property_rejected_view_edit' => 'Para visualizar ou editar sua propriedade, clique neste link:',

        // Account registered email
        'account_registered_message' => 'Uma nova conta registrada:',

        // Account approved email
        'account_approved_title' => 'Conta aprovada',
        'account_approved_greeting' => 'Prezado :name,',
        'account_approved_congratulations' => 'Parabéns! Sua conta em :site_name foi aprovada.',
        'account_approved_login' => 'Agora você pode fazer login e começar a usar nossos serviços. Se você tiver alguma dúvida, sinta-se à vontade para entrar em contato com nossa equipe de suporte.',
        'account_approved_thanks' => 'Obrigado por se juntar a :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Conta rejeitada',
        'account_rejected_greeting' => 'Prezado :name,',
        'account_rejected_regret' => 'Lamentamos informar que sua conta em :site_name foi rejeitada.',
        'account_rejected_reason' => 'Motivo da rejeição:',
        'account_rejected_contact' => 'Se você tiver alguma dúvida ou acreditar que isso é um erro, entre em contato com nossa equipe de suporte.',
        'account_rejected_thanks' => 'Obrigado pela sua compreensão.',

        // Confirm email
        'confirm_email_greeting' => 'Olá!',
        'confirm_email_verify' => 'Verifique seu endereço de e-mail para acessar este site. Clique no botão abaixo para verificar seu e-mail.',
        'confirm_email_trouble' => 'Se você estiver com problemas para clicar no botão "Verificar agora", copie e cole o URL abaixo em seu navegador:',

        // Password reminder email
        'password_reminder_greeting' => 'Olá!',
        'password_reminder_request' => 'Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha da sua conta.',
        'password_reminder_no_action' => 'Se você não solicitou uma redefinição de senha, nenhuma ação adicional será necessária.',
        'password_reminder_trouble' => 'Se você estiver com problemas para clicar no botão "Redefinir senha", copie e cole o URL abaixo em seu navegador:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Olá :name,',
        'payment_receipt_title' => 'Comprovante de pagamento da sua compra:',

        // Payment received email (admin)
        'payment_received_message' => 'Pagamento recebido de :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name reivindicou créditos gratuitos.',

        // Notice email (consult form)
        'notice_title' => 'Nova solicitação de consulta',
        'notice_message' => 'Há uma nova consulta de :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Obrigado pelo seu pedido de consulta!',
        'sender_confirmation_greeting' => 'Prezado :name,',
        'sender_confirmation_thank_you' => 'Obrigado por entrar em contato conosco! Recebemos sua solicitação de consulta e nossa equipe irá analisá-la em breve. Um de nossos representantes entrará em contato com você o mais breve possível.',
        'sender_confirmation_submission_details' => 'Aqui estão os detalhes do seu envio:',
        'sender_confirmation_additional_info' => 'Se você tiver alguma informação ou dúvida adicional, sinta-se à vontade para responder a este e-mail.',
        'sender_confirmation_appreciate' => 'Agradecemos seu interesse e entraremos em contato em breve!',
    ],
];
