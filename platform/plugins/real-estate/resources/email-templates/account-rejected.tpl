{{ header }}
<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-red" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td valign="middle" align="center">
                            <img src="{{ 'alert-triangle' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/real-estate::real-estate.email_templates.account_rejected_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td class="bb-content">
                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_rejected_greeting' | trans({'name': account_name}) }}</p>

                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_rejected_regret' | trans({'site_name': site_title}) }}</p>

                            {% if rejection_reason %}
                            <p><strong>{{ 'plugins/real-estate::real-estate.email_templates.account_rejected_reason' | trans }}</strong> {{ rejection_reason }}</p>
                            {% endif %}

                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_rejected_contact' | trans }}</p>

                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_rejected_thanks' | trans }}</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
