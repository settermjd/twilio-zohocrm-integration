<!-- markdownlint-disable MD013 -->

# Twilio / Zoho CRM Integration

This is a small PHP application that shows how to integrate Zoho CRM with Twilio.

## Overview

The application itself isn't meant to be all that sophisticated, feature rich, or to be written with production-ready code.
Rather, it demonstrates the essentials of the following integrations (more to come):

- **How to integrate Zoho CRM's API with Twilio's Programmable Messaging API.**
  This integration shows how to notify meeting participants by SMS when they're involved in a new meeting, or when details of an existing meeting change.

## Prerequisites

You'll need the following to use the application:

- A Twilio account.
  [Sign up for free today][twilio-signup] if you don't have an account.
- A Zoho CRM Professional account.
  [Sign up for a 15-day free trial][zoho-crm-trial-signup] if you don't have an account
- PHP 8.3 or above (ideally 8.4 or 8.5)
- [Composer][composer] installed globally
- [ngrok][ngrok] or [a similar tool][ngrok-alternatives] for exposing a locally running application to the public internet
- Your preferred code editor or IDE
- Some terminal experience is helpful, though not required

## Quick Start

To start using the application, clone the repository wherever you store your PHP apps, change into the cloned project, and install the project's dependencies by running the following commands.

```bash
git clone ...
cd ...
composer install \
  --no-dev --no-ansi --no-plugins --no-progress --no-scripts \
  --classmap-authoritative --no-interaction \
  --quiet
```

Then, you need set the required environment variables in _.env_.
These are:

| Environment Variable                       | Description                                                                                                                                                                                                  |
| ------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `PUBLIC_URL`                               | This is the application's public-facing URL. When developing locally, use an app/binary such as ngrok to create a (secure) tunnel to the internet, thereby creating a public facing URL for the application. |
| `TWILIO_ACCOUNT_SID` & `TWILIO_AUTH_TOKEN` | These are your Twilio Account SID Auth Token. They are required to make authenticated requests to Twilio's Programmable Messaging API.                                                                       |
| `TWILIO_PHONE_NUMBER`                      | This is your Twilio phone number, required to send SMS notifications to customers.                                                                                                                           |
| `ZOHO_CLIENT_ID` & `ZOHO_CLIENT_SECRET`    | These are the client id and secret, required to retrieve an OAuth token from Zoho CRM to make authenticated requests to the Zoho CRM API.                                                                    |
| `ZOHO_SOID`                                | This is the organisation ID of your Zoho CRM organisation.                                                                                                                                                   |
| `ZOHOCRM_DC`                               | This is the data center assigned to your account, e.g., "AU".                                                                                                                                                |
| `ZOHOCRM_URI`                              | This is the base API URI most applicable to you, e.g., "<https://www.zohoapis.com.au/crm/v8>".                                                                                                               |

After the environment variables are set, start the application with PHP's built-in webserver by running the following command:

```bash
php -S 0.0.0.0:8080 -t public/
```

Then, in a new terminal tab or session, expose it to the public internet by running the following command:

```bash
ngrok http 8080
```

Then, you need to:

- [Create a webhook and associate it with a workflow][create-zohocrm-webhook-and-associate-it]
- [Create the meeting in the Zoho CRM console][zoho-crm-meetings-docs]

Following the creation of the meeting, you'll receive an SMS, letting you know that you are a participant in the meeting that you just created, along with its location and start date and time.

> [!TIP]
> You can find full details about the application and how to set it up, in the tutorial based on this codebase, [which is available on the Twilio blog][tutorial-part-one].

## Contributing

If you want to contribute to the project, whether you have found issues with it or just want to improve it, here's how:

- [Issues][github-issues]: ask questions and submit your feature requests, bug reports, etc
- [Pull requests][github-prs]: send your improvements

## Resources

- [The CodeExchange repository][codeexchange-repo]

## Did You Find The Project Useful?

If the project was useful and you want to say thank you and/or support its active development, here's how:

- Add a GitHub Star to the project
- Write an interesting article about the project wherever you blog

## License

[MIT][mit-license]

## Disclaimer

No warranty expressed or implied. Software is as is.

<!-- Links -->

[codeexchange-repo]: https://github.com/twilio-labs/code-exchange/
[composer]: https://getcomposer.org
[create-zohocrm-webhook-and-associate-it]: https://help.zoho.com/portal/en/kb/crm/automate-business-processes/actions/articles/webhooks-workflow
[github-issues]: https://github.com/settermjd/block-spam-calls-php/issues
[github-prs]: https://github.com/settermjd/block-spam-calls-php/pulls
[mit-license]: http://www.opensource.org/licenses/mit-license.html
[ngrok-alternatives]: https://github.com/anderspitman/awesome-tunneling
[ngrok]: https://ngrok.com
[tutorial-part-one]: https://www.twilio.com/en-us/blog/developers/tutorials/build-twilio-zoho-crm-sms-integration
[twilio-signup]: https://twilio.com/try-twilio
[zoho-crm-meetings-docs]: https://help.zoho.com/portal/en/kb/crm/sales-force-automation/activities/articles/working-with-meetings-new
[zoho-crm-trial-signup]: https://www.zoho.com/crm/signup.html?plan=professional&source_from=crmpricing

<!-- markdownlint-enable MD013 -->
