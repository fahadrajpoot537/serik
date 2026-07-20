{{ header }}

<p>{{ 'plugins/real-estate::real-estate.email_templates.greeting_admin' | trans }}</p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.free_credit_claimed_message' | trans({'name': '<strong>' ~ account_name ~ '</strong> (' ~ account_email ~ ')'}) | raw }}</p>

{{ footer }}
