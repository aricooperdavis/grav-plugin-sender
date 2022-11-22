# Sender Plugin

The **Sender** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav).

This plugin adds form actions that interface with the [Sender mailing service](https://www.sender.net/), allowing you to register subscribers directly from your site.

Sender has a generous ["free forever" tier](https://www.sender.net/pricing/) that allows you to register up to 2,500 subscribers and send up to 15,000 (discretely Sender-branded) emails per month, which is more than adequate for many small blogs.

## Installation

Installing the Sender plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install sender

This will install the Sender plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/sender`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `sender`. You can find these files on [GitHub](https://github.com/aricooperdavis/grav-plugin-sender) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/sender
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/aricooperdavis/grav-plugin-sender/blob/master/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/sender/sender.yaml` to `user/config/plugins/sender.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
sender_token: your_sender_api_token
messages:
  success: 'Thanks, you''re now subscribed 😃' # Note escaped single quote
  error: 'Sorry, something went wrong, please try again later 😢'
```

Note that if you use the Admin Plugin, a file with your configuration named sender.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

It's really simple to get your forms working with the Sender API:

1. [Generate an API token](https://app.sender.net/settings/tokens) in your Sender account and add it to the plugin configuration.
2. Create a form the form action added by this plugin:
    - `sender-subscribe`:
        
        This action [creates a new subscriber](https://api.sender.net/subscribers/add-subscriber/) with the properties given in the form data. Any fields given that do not appear in the API method definition will be ignored.
        
        Valid form field names are:

        - `email` (required): the email address of the new subscriber
        - `firstname`: the firstname of the new subscriber
        - `lastname`: the lastname of the new subscriber
        - `fields.*`: custom key-value pairs (referred to as `fields` by Sender)
        - `phone`: the phone number of the new subscriber

        This action takes two arguments:
        - `groups` (required): an array of the group IDs to which the new subscriber should be added
        - `trigger_automation`: whether the action activates automation (default: `true`)

And that's it!

[AJAX submission](https://learn.getgrav.org/17/forms/forms/how-to-ajax-submission) works as normal. I'd recommend following Sender's guide to [implement double opt-in](https://help.sender.net/knowledgebase/double-opt-in/) too.

Any failures should be recorded in the Grav debug log, which might be useful to check whilst you're setting up the plugin.

## Example

An example `form.md` header might be as follows:

```yaml
---
form:
    name: signup-form
    fields:
        firstname:
            label: 'Name:'
            type: text
        email:
            label: 'Email:'
            type: email
            validate:
                required: true
        fields:
            type: fieldset
            fields:
                fields.website:
                    label: Your Web Address
                    type: text
        captcha:
            type: turnstile
            theme: light
    buttons:
        submit:
            type: submit
            value: Sign-up
    process:
        turnstile: true
        sender-subscribe:
            groups: 
                - groupId
            trigger-automation: false
        display: '/thanks'
---
```

Some things to note about the above example:
- We have to define our `groups` as an array, even though it only contains one value.
- We can include fields, such as `captcha`, that aren't in the API definition, and these will work as intended but won't be passed to the Sender API call.
- Custom fields for inclusion in the Sender API call need to be named `fields.*` in order to be included.
- We can add other actions before and after the `sender-subscribe` action and they'll behave properly (although the `sender-subscribe` action overwrites the form message set by the `message` action in order to report the results of the Sender API call).

## Contributing

Yes please - issues, translations, improvements etc. are all greatly appreciated.

## Credits

This plugin uses [`guzzlehttp/guzzle`](https://packagist.org/packages/guzzlehttp/guzzle) for making the API calls.

## To Do

- [ ] Add a `sender-unsubscribe` form action that leverages the [`remove-group` method](https://api.sender.net/subscribers/remove-group/)
- [ ] Add a `sender-delete` form action that leverages the [`delete-subscriber` method](https://api.sender.net/subscribers/delete-subscriber/)

