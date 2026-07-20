{{ header }}

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_approved_greeting' | trans({'name': author_name}) | raw }}</p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_approved_message' | trans({'property_name': property_name, 'site_name': site_title}) }} {{ 'plugins/real-estate::real-estate.email_templates.property_approved_access' | trans }}</p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.property_approved_view_edit' | trans }} <a href="{{ property_link }}">{{ 'plugins/real-estate::real-estate.email_templates.action_view_property' | trans }}</a></p>

<p>{{ 'plugins/real-estate::real-estate.email_templates.closing_regards' | trans }}</p>

<p><strong>{{ site_title }}</strong></p>

{{ footer }}
