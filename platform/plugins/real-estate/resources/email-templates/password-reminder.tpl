{{ header }}

<strong>{{ 'plugins/real-estate::real-estate.email_templates.password_reminder_greeting' | trans }}</strong> <br /><br />

{{ 'plugins/real-estate::real-estate.email_templates.password_reminder_request' | trans }} <br /><br />

<a href="{{ reset_link }}">{{ 'plugins/real-estate::real-estate.email_templates.action_reset_password' | trans }}</a> <br /><br />

{{ 'plugins/real-estate::real-estate.email_templates.password_reminder_no_action' | trans }} <br /><br />

{{ 'plugins/real-estate::real-estate.email_templates.closing_regards' | trans }} <br />

<strong>{{ site_title }}</strong>

<hr />

{{ 'plugins/real-estate::real-estate.email_templates.password_reminder_trouble' | trans }} {{ reset_link }}

{{ footer }}
