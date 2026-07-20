{{ header }}

<p>{{ 'plugins/real-estate::real-estate.email_templates.payment_receipt_greeting' | trans({'name': account_name}) }}</p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.payment_receipt_title' | trans }}</p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.field_package' | trans }} <strong>{{ package_name }}</strong></p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.field_price' | trans }} <strong>{{ package_price_per_credit | price_format }}{{ 'plugins/real-estate::real-estate.email_templates.per_credit' | trans }}</strong></p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.field_total' | trans }} <strong>{{ package_price | price_format }} {{ 'plugins/real-estate::real-estate.email_templates.for_credits' | trans({'count': package_number_of_listings}) }} {% if package_percent_discount > 0 %} {{ 'plugins/real-estate::real-estate.email_templates.save_amount' | trans({'amount': package_percent_discount ~ '%'}) }} {% endif %}</strong></p>

{{ footer }}
