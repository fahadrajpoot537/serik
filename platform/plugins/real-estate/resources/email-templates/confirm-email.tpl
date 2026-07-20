{{ header }}

<strong>{{ 'plugins/real-estate::real-estate.email_templates.confirm_email_greeting' | trans }}</strong> <br /><br />

{{ 'plugins/real-estate::real-estate.email_templates.confirm_email_verify' | trans }} <br /><br />

<a href="{{ verify_link }}">{{ 'plugins/real-estate::real-estate.email_templates.action_verify_now' | trans }}</a> <br /><br />

{{ 'plugins/real-estate::real-estate.email_templates.closing_regards' | trans }} <br />

<strong>{{ site_title }}</strong>

<hr />

{{ 'plugins/real-estate::real-estate.email_templates.confirm_email_trouble' | trans }} {{ verify_link }}

{{ footer }}
