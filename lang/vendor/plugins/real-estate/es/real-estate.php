<?php

return [
    'name' => 'Bienes Raíces',
    'settings' => 'Configuración',
    'login_form' => 'Formulario de Inicio de Sesión',
    'register_form' => 'Formulario de Registro',
    'forgot_password_form' => 'Formulario de Contraseña Olvidada',
    'reset_password_form' => 'Formulario de Restablecer Contraseña',
    'consult_form' => 'Formulario de Consulta',
    'review_form' => 'Formulario de Reseña',
    'property_translations' => 'Traducciones de Propiedades',
    'project_translations' => 'Traducciones de Proyectos',
    'theme_options' => [
        'slug_name' => 'URLs de Bienes Raíces',
        'slug_description' => 'Personaliza los slugs utilizados para las páginas de bienes raíces. Ten cuidado al modificar ya que puede afectar el SEO y la experiencia del usuario. Si algo sale mal, puedes restablecer al valor predeterminado escribiendo el valor predeterminado o dejándolo en blanco.',
        'page_slug_name' => 'Slug de página :page',
        'page_slug_description' => 'Se verá como :slug cuando accedas a la página. El valor predeterminado es :default.',
        'page_slug_already_exists' => 'El slug de página :slug ya está en uso. Por favor elige otro.',
        'page_slugs' => [
            'projects_city' => 'Proyectos por Ciudad',
            'projects_state' => 'Proyectos por Estado',
            'properties_city' => 'Propiedades por Ciudad',
            'properties_state' => 'Propiedades por Estado',
            'login' => 'Iniciar Sesión',
            'register' => 'Registrarse',
            'reset_password' => 'Restablecer Contraseña',
        ],
    ],
    'email_templates' => [
        // Etiquetas de campos comunes
        'field_name' => 'Nombre:',
        'field_email' => 'Correo electrónico:',
        'field_phone' => 'Teléfono:',
        'field_subject' => 'Asunto:',
        'field_content' => 'Contenido:',
        'field_address' => 'Dirección:',
        'field_package' => 'Paquete:',
        'field_price' => 'Precio:',
        'field_total' => 'Total:',
        'field_account' => 'Cuenta:',

        // Saludos y despedidas comunes
        'greeting_admin' => '¡Hola Admin!',
        'greeting_hello' => 'Hola',
        'greeting_dear' => 'Estimado/a',
        'greeting_dear_admin' => 'Estimado Admin,',
        'closing_regards' => 'Saludos,',
        'closing_thank_you' => 'Gracias',
        'closing_best_regards' => 'Cordiales saludos,',

        // Acciones comunes
        'action_view_property' => 'Ver Propiedad',
        'action_verify_now' => 'Verificar ahora',
        'action_reset_password' => 'Restablecer contraseña',

        // Términos comunes
        'credits' => 'créditos',
        'per_credit' => '/crédito',
        'save_amount' => '(Ahorra :amount)',
        'for_credits' => 'por :count créditos',

        // Nuevo email de propiedad pendiente
        'new_pending_property_message' => 'Una nueva propiedad creada por :author ":name" está esperando aprobación.',

        // Email de propiedad aprobada
        'property_approved_greeting' => 'Hola :name,',
        'property_approved_message' => 'Tu propiedad ":property_name" ha sido aprobada exitosamente en :site_name.',
        'property_approved_access' => 'Ya puedes acceder al sitio web y gestionar tu propiedad.',
        'property_approved_view_edit' => 'Para ver o editar tu propiedad, por favor haz clic en este enlace:',

        // Email de propiedad rechazada
        'property_rejected_greeting' => 'Hola :name,',
        'property_rejected_message' => 'Tu propiedad ":property_name" ha sido rechazada en :site_name.',
        'property_rejected_reason' => 'La razón del rechazo es la siguiente:',
        'property_rejected_contact' => 'Si crees que esta decisión fue un error, por favor contacta a nuestro equipo de soporte en :email.',
        'property_rejected_view_edit' => 'Para ver o editar tu propiedad, por favor haz clic en este enlace:',

        // Email de cuenta registrada
        'account_registered_message' => 'Se ha registrado una nueva cuenta:',

        // Email de cuenta aprobada
        'account_approved_title' => 'Cuenta Aprobada',
        'account_approved_greeting' => 'Estimado/a :name,',
        'account_approved_congratulations' => '¡Felicitaciones! Tu cuenta en :site_name ha sido aprobada.',
        'account_approved_login' => 'Ya puedes iniciar sesión y comenzar a usar nuestros servicios. Si tienes alguna pregunta, no dudes en contactar a nuestro equipo de soporte.',
        'account_approved_thanks' => '¡Gracias por unirte a :site_name!',

        // Email de cuenta rechazada
        'account_rejected_title' => 'Cuenta Rechazada',
        'account_rejected_greeting' => 'Estimado/a :name,',
        'account_rejected_regret' => 'Lamentamos informarte que tu cuenta en :site_name ha sido rechazada.',
        'account_rejected_reason' => 'Razón del rechazo:',
        'account_rejected_contact' => 'Si tienes alguna pregunta o crees que esto es un error, por favor contacta a nuestro equipo de soporte.',
        'account_rejected_thanks' => 'Gracias por tu comprensión.',

        // Email de confirmación
        'confirm_email_greeting' => '¡Hola!',
        'confirm_email_verify' => 'Por favor verifica tu dirección de correo electrónico para poder acceder a este sitio web. Haz clic en el botón de abajo para verificar tu correo.',
        'confirm_email_trouble' => 'Si tienes problemas haciendo clic en el botón "Verificar ahora", copia y pega la URL de abajo en tu navegador web:',

        // Email de recordatorio de contraseña
        'password_reminder_greeting' => '¡Hola!',
        'password_reminder_request' => 'Estás recibiendo este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta.',
        'password_reminder_no_action' => 'Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna acción adicional.',
        'password_reminder_trouble' => 'Si tienes problemas haciendo clic en el botón "Restablecer Contraseña", copia y pega la URL de abajo en tu navegador web:',

        // Email de recibo de pago
        'payment_receipt_greeting' => 'Hola :name,',
        'payment_receipt_title' => 'Recibo de pago por tu compra:',

        // Email de pago recibido (admin)
        'payment_received_message' => 'Pago recibido de :name',

        // Email de crédito gratuito reclamado
        'free_credit_claimed_message' => ':name ha reclamado créditos gratuitos.',

        // Email de aviso (formulario de consulta)
        'notice_title' => 'Nueva Solicitud de Consulta',
        'notice_message' => 'Hay una nueva consulta de :property_name',

        // Email de confirmación al remitente (formulario de consulta)
        'sender_confirmation_title' => '¡Gracias por tu Solicitud de Consulta!',
        'sender_confirmation_greeting' => 'Estimado/a :name,',
        'sender_confirmation_thank_you' => '¡Gracias por contactarnos! Hemos recibido tu solicitud de consulta y nuestro equipo la revisará en breve. Uno de nuestros representantes se pondrá en contacto contigo lo antes posible.',
        'sender_confirmation_submission_details' => 'Aquí están los detalles de tu envío:',
        'sender_confirmation_additional_info' => 'Si tienes información adicional o preguntas, no dudes en responder a este correo.',
        'sender_confirmation_appreciate' => '¡Apreciamos tu interés y nos pondremos en contacto pronto!',
    ],
    'contact_for_price' => 'Contacto',
    'contact_for_price' => 'Contacto',
    'yes' => 'Sí',
    'no' => 'No',
    'projects' => 'Proyectos',
    'properties' => 'Propiedades',
    'agents' => 'Agentes',
    'projects_in_city' => 'Proyectos en :city',
    'properties_in_city' => 'Propiedades en :city',
    'projects_in_state' => 'Proyectos en :state',
    'properties_in_state' => 'Propiedades en :state',
    'sort_date_asc' => 'Más antiguo',
    'sort_date_desc' => 'Más reciente',
    'sort_price_asc' => 'Precio (de menor a mayor)',
    'sort_price_desc' => 'Precio (de mayor a menor)',
    'sort_name_asc' => 'Nombre (A-Z)',
    'sort_name_desc' => 'Nombre (Z-A)',
    'area_unit_m2' => 'm²',
    'area_unit_ft2' => 'ft2',
    'area_unit_yd2' => 'yd2',
    'edit_property' => 'Editar esta propiedad',
    'edit_project' => 'Editar este proyecto',
    'edit_agent' => 'Editar este agente',
    'property_no_longer_available' => 'Propiedad ya no disponible: :name',
    'property_listing_expired' => 'Este anuncio de propiedad ha caducado',
    'property_listing_no_longer_available' => 'Este anuncio de propiedad ya no está disponible',
    'property_expired_title' => 'Propiedad caducada: :name',
];
