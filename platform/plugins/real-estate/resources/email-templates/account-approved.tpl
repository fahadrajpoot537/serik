{{ header }}
<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-green" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td valign="middle" align="center">
                            <img src="{{ 'check' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/real-estate::real-estate.email_templates.account_approved_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td class="bb-content">
                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_approved_greeting' | trans({'name': account_name}) }}</p>

                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_approved_congratulations' | trans({'site_name': site_title}) }}</p>

                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_approved_login' | trans }}</p>

                            <p>{{ 'plugins/real-estate::real-estate.email_templates.account_approved_thanks' | trans({'site_name': site_title}) }}</p>
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
