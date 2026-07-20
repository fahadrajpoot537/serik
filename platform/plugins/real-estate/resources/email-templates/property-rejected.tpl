{{ header }}

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_rejected_greeting' | trans({'name': author_name}) | raw }}</p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_rejected_message' | trans({'property_name': property_name, 'site_name': site_title}) }}</p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_rejected_reason' | trans }} <strong>{{ reason }}</strong>. {{ 'plugins/real-estate::real-estate.email_templates.property_rejected_contact' | trans({'email': '<a href="mailto:' ~ site_email ~ '">' ~ site_email ~ '</a>'}) | raw }}</p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_rejected_view_edit' | trans }} <a href="{{ property_link }}">{{ 'plugins/real-estate::real-estate.email_templates.action_view_property' | trans }}</a></p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.closing_regards' | trans }}</p>

<p><strong>{{ site_title }}</strong></p>

{{ footer }}
