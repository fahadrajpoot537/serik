{{ header }}

<p>{{ 'plugins/real-estate::real-estate.email_templates.greeting_admin' | trans }}</p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.new_pending_property_message' | trans({'author': post_author, 'name': '<a href="' ~ post_url ~ '">' ~ post_name ~ '</a>'}) | raw }}</p>

{{ footer }}
