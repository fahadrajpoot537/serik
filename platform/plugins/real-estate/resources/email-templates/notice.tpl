{{ header }}
<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td valign="middle" align="center">
                            <img src="{{ 'mail' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/real-estate::real-estate.email_templates.notice_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td class="bb-content">
                            <p>{{ 'plugins/real-estate::real-estate.email_templates.greeting_dear_admin' | trans }}</p>

                            <h4>{{ 'plugins/real-estate::real-estate.email_templates.notice_message' | trans({'property_name': site_title}) }}</h4>

                            <table class="bb-table" cellspacing="0" cellpadding="0">
                                <thead>
                                <tr>
                                    <th width="120px"></th>
                                </tr>
                                </thead>
                                <tbody>
                                {% if consult_name %}
                                <tr>
                                    <td>{{ 'plugins/real-estate::real-estate.email_templates.field_name' | trans }}</td>
                                    <td class="bb-font-strong bb-text-left"> {{ consult_name }} ({{ consult_ip_address }})</td>
                                </tr>
                                {% endif %}
                                {% if consult_subject %}
                                <tr>
                                    <td>{{ 'plugins/real-estate::real-estate.email_templates.field_subject' | trans }}</td>
                                    <td class="bb-font-strong bb-text-left"> <a href="{{ consult_link }}">{{ consult_subject }}</a> </td>
                                </tr>
                                {% endif %}
                                {% if consult_email %}
                                <tr>
                                    <td>{{ 'plugins/real-estate::real-estate.email_templates.field_email' | trans }}</td>
                                    <td class="bb-font-strong bb-text-left"> {{ consult_email }} </td>
                                </tr>
                                {% endif %}
                                {% if consult_address %}
                                <tr>
                                    <td>{{ 'plugins/real-estate::real-estate.email_templates.field_address' | trans }}</td>
                                    <td class="bb-font-strong bb-text-left"> {{ consult_address }} </td>
                                </tr>
                                {% endif %}
                                {% if consult_phone %}
                                <tr>
                                    <td>{{ 'plugins/real-estate::real-estate.email_templates.field_phone' | trans }}</td>
                                    <td class="bb-font-strong bb-text-left"> {{ consult_phone }} </td>
                                </tr>
                                {% endif %}
                                {% for key, value in consult_custom_fields %}
                                    <tr>
                                        <td>{{ key }}:</td>
                                        <td class="bb-font-strong bb-text-left"> {{ value }} </td>
                                    </tr>
                                {% endfor %}
                                {% if consult_content %}
                                <tr>
                                    <td colspan="2">{{ 'plugins/real-estate::real-estate.email_templates.field_content' | trans }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="bb-font-strong"><i>{{ consult_content }}</i></td>
                                </tr>
                                {% endif %}
                                </tbody>
                            </table>
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
